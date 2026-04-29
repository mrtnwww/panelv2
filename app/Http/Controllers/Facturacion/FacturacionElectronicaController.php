<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FacturacionElectronica;
use App\Models\Usuario;
use App\Services\FacturacionElectronicaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FacturacionElectronicaController extends Controller
{
    public function ciudad(Request $request, FacturacionElectronicaService $feService)
    {
        $payload = $this->getPayload();

        $data = $request->validate([
            'ciudad' => 'required|string'
        ]);

        $fe = $feService->client($payload['user']['empresa_id']);
        $baseUrl = $fe['baseUrl'];

        $ciudad = $fe['http']->get($baseUrl . 'CiudadesFind', [
            'todos' => 'false',
            'idCiudad' => '',
            'descripcion' => $data['ciudad']
        ]);

        if ($ciudad->successful()) {
            return response()->json([
                'ciudad' => $ciudad->json()
            ], 200);
        } else {
            return response()->json([
                'message' => 'Error consultando la ciudad en el software de facturación electrónica.'
            ], 500);
        }
    }

    public function configFE()
    {
        $payload = $this->getPayload();

        $configuracion = FacturacionElectronica::where('empresa_id', $payload['user']['empresa_id'])->first();

        if ($configuracion) {
            return response()->json([
                'configuracion' => [
                    'nombre' => $configuracion->nombre,
                    'id' => $configuracion->id,
                    'url' => $configuracion->url
                ]
            ], 200);
        } else {
            return response()->json([
                'configuracion' => null
            ], 200);
        }
    }

    public function combosFacturacion(Request $request, FacturacionElectronicaService $feService)
    {
        $payload = $this->getPayload();

        $fe = $feService->client($payload['user']['empresa_id']);
        if (!$fe) {
            return response()->json([
                'message' => 'No se encontró una configuración de facturación electrónica para esta empresa. Por favor configure la conexión antes de consultar los datos requeridos para la creación de terceros.'
            ], 404);
        }

        $baseUrl = $fe['baseUrl'];
        $http = $fe['http'];

        $clientesDirecciones = $http->get($baseUrl . 'ClientesTiposDireccionesFind', ['todos' => 'true']);
        $tiposIdentificacion = $http->get($baseUrl . 'TipoIdentificacionFind', ['todos' => 'true']);
        $clientesTipos = $http->get($baseUrl . 'ClientesTiposFillsCombos');
        $tiposRegimen = $http->get($baseUrl . 'TipoRegimenFillsCombos');

        if (
            !$tiposIdentificacion->successful() ||
            !$clientesDirecciones->successful() ||
            !$clientesTipos->successful() ||
            !$tiposRegimen->successful()
        ) {
            return response()->json([
                'message' => 'Error consultando los datos requeridos para la creación de terceros - FE'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'tiposIdentificacion' => $tiposIdentificacion->json(),
            'clientesTiposDirecciones' => $clientesDirecciones->json(),
            'clientesTipos' => $clientesTipos->json(),
            'tiposRegimen' => $tiposRegimen->json()
        ]);
    }

    public function consultarClientes(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $perPage = $request->input('perPage', 10);
        $search = $request->input('search', '');

        $empresasIds = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id')
            ->push($empresaId)
            ->unique()
            ->values();

        $usuariosIds = Usuario::whereIn('empresa_id', $empresasIds)
            ->where('subtipousuario_id', '!=', 7)
            ->withTrashed()
            ->pluck('id');

        // clientes con abonos facturados y pendiente de crear el el software de FE
        $clientes = Cliente::whereHas('credito.abonos', function ($q) use ($usuariosIds) {
            $q->whereIn('user_id', $usuariosIds)
                ->where('abono_factura', 1);
        })
            ->where(function ($q) {
                $q->where('clienteFE', false)->orWhereNull('clienteFE');
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('cedula', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate($perPage);

        return response()->json(['clientes' => $clientes]);
    }

    public function registrarTerceros(Request $request, FacturacionElectronicaService $feService)
    {
        $usuarioId = auth()->user()->id;
        $empresaId = auth()->user()->empresa_id;

        $infoTerceros = $request->input('data', []);

        try {
            $fe = $feService->client($empresaId);
            $baseUrl = $fe['baseUrl'];
            $http = $fe['http'];

            \Log::info('Inicio registro de terceros FE - Usuario ID: ' . $usuarioId . ' - ' . now()->toDateTimeString());

            foreach ($infoTerceros as $index => $tercero) {
                $cliente = Cliente::with('empresa')->find($tercero['id']);

                if (!$cliente) {
                    \Log::warning('Cliente no encontrado: ' . $tercero['id']);
                    continue;
                }

                \Log::info('Registrando cliente ID: ' . $cliente->id . ' - ' . $cliente->nombre);

                // info tipo de cliente
                $partes = explode('__', $tercero['clientesTipos'], 2);
                $idTipoCliente = $partes[0] ?? null;
                $nomTipoCliente = isset($partes[1]) ? trim($partes[1]) : null;

                if (!$idTipoCliente || !$nomTipoCliente) {
                    \Log::error('clientesTipos mal formado', $tercero);
                    continue;
                }

                $idCiudad = 11001; // Bogota D.C. por defecto

                $data = [
                    'IdCliente' => 0,
                    'IdTipoIdent' => $tercero['tiposId'],
                    'Identificacion' => $cliente->cedula,
                    'IdCiudad' => $idCiudad,
                    'Nombre1' => $tercero['nombre1'],
                    'Nombre2' => $tercero['nombre2'],
                    'Apellido1' => $tercero['apellido1'],
                    'Apellido2' => $tercero['apellido2'],
                    'EsActivo' => true,
                    'IdTipoRegimen' => $tercero['tiposRegimen'],
                    'EsEspecial' => false,
                    'EsAutoRetenedor' => false,
                    'Clientes_Tipos' => [
                        [
                            'IdTipo' => $idTipoCliente,
                            'NomTipo' => trim($nomTipoCliente)
                        ]
                    ],
                    "FacturacionElectronica_ResponsabilidadFiscal" => [
                        [
                            "IdResFiscal" => 114,
                            "CodResFiscal" => "R-99-PN",
                            "NomResFiscal" => "No responsable"
                        ]
                    ],
                    'Impuestos_Tipos' => [
                        [
                            'IdTipo' => 6,
                            'CodTipo' => 'ZZ',
                            'NomTipo' => 'No aplica'
                        ]
                    ],
                    'Clientes_Direcciones' => [
                        [
                            'IdCliente' => 0,
                            'IdTipoDir' => $tercero['clientesDirecciones'],
                            'Direccion' => $cliente->empresa->razon_social,
                            'DireccionFormato1' => $cliente->empresa->razon_social,
                            "Telefonos" => $cliente->telefono,
                            "Celular" => $cliente->telefono,
                            'EMail' => $cliente->email,
                            'IdCiudad' => $idCiudad,
                            'EsDirPrincipal' => true
                        ]
                    ]
                ];

                // registrar tercero
                $respuesta = $http->post($baseUrl . 'ClientesSaveChanges', $data);

                \Log::debug($respuesta);

                if ($respuesta->json('ErrorCode') != 0) {
                    \Log::error('Error registrando el cliente en el software de facturación electrónica.');

                    return response()->json([
                        'status' => 400,
                        'message' => $respuesta->json('ErrorMessage') ?? 'Error registrando el cliente ' . $cliente->nombre . ' en el software de facturación electrónica.'
                    ], 400);
                }

                $cliente->clienteFE_fecha_registro = Carbon::now();
                $cliente->clienteFE_id = $respuesta->json('IdNumber1') ?? null;
                $cliente->clienteFE = true;
                $cliente->save();

                \Log::info("Cliente " . $cliente->id . " registrado correctamente en el sw de FE con ID: " . $cliente->clienteFE_id);
            }

            \Log::info('Fin registro de terceros FE - Usuario ID: ' . $usuarioId . ' - ' . now()->toDateTimeString());
            \Log::debug('----------------------------');

            return response()->json([
                'status' => 200,
                'message' => 'Terceros registrados correctamente en el software de facturación electrónica.'
            ], 200);
        } catch (\Exception $ex) {
            \Log::error('Error inesperado en registrarTerceros: ' . $ex->getMessage(), [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Ocurrió un error inesperado. Por favor contacte al administrador.'
            ], 500);
        }
    }

    public function crearEncabezadoDocumento(Request $request, FacturacionElectronicaService $feService)
    {
        $payload = $this->getPayload();

        // validar abono_id y cliente_id
        $data = $request->validate([
            'abono' => 'required|integer',
            'cliente' => 'required|integer'
        ]);

        try {
            $fe = $feService->client($payload['user']['empresa_id']);
            $baseUrl = $fe['baseUrl'];
            $http = $fe['http'];

            // fecha formateada para enviar en la request API
            $fecha = Carbon::now('America/Bogota');
            $timestampMs = $fecha->timestamp * 1000;
            $offset = $fecha->format('O'); // -0500
            $fechaFE = "/Date({$timestampMs}{$offset})/";

            // id encabezado de documento
            $IdEncab = null;

            // obtener la direccion del cliente
            $idTipoDir = $http->get($baseUrl . 'ClientesDirGet', [
                'idCliente' => $data['cliente']
            ]);

            if (count($idTipoDir->json()) < 1) {
                \Log::error('No se pudo obtener la dirección del cliente', ['cliente' => $data['cliente']]);
                return response()->json([
                    'message' => 'No se pudo obtener la dirección del cliente para crear el encabezado.'
                ], 422);
            }

            $idClienteDir = $idTipoDir->json()[0]['IdClienteDir'] ?? null;

            if (!$idClienteDir) {
                return response()->json([
                    'message' => 'La dirección del cliente no tiene un ID válido.'
                ], 422);
            }

            // crear encabezado de documento
            $encabezadoData = [
                "IdEncab" => 0,
                "IdEmpresa" => 1, // FUSION CORP SAS
                "IdPrefijo" => 'CR',
                "NumDocumento" => 0,
                "EsAnulado" => false,
                "EsBloqueado" => false,
                "EsFeSincronizada" => false,
                "EsImpreso" => false,
                "EsRevisado" => false,
                "FeEnvioAnticipo" => false,
                "FeIdVersion" => 0,
                "Fecha" => $fechaFE,
                "FechaCreacion" => $fechaFE,
                "FinanDebe" => false,
                "IdCliente1" => $data['cliente'],
                "IdCliente1_IdDir" => $idClienteDir,
                "IdDocumento" => 2,   // Factura de venta
                "IdEstado" => -1,  // Iniciado
                "IdMoneda" => 1,   // COP
                "NomPc" => "FUSION CORP SAS / WEBMASTER",
                "ValorTRM" => 0,
                "Vence" => $fechaFE,
                "FechaUltModificacion" => $fechaFE
            ];

            $creacionEncabezado = $http->post($baseUrl . 'EncabezadosSaveChanges', $encabezadoData);

            if ($creacionEncabezado->json('ErrorCode') != 0) {
                return response()->json([
                    'message' => $creacionEncabezado->json('ErrorMessage') ?? 'Ocurrió un error al crear el encabezado'
                ], 400);
            }

            $IdEncab = $creacionEncabezado->json('IdNumber1') ?? null;

            if ($IdEncab) {
                /*
                    FACTURAR SERVICIO DE FINANCIACION
                        - SERVICIO DE MORA */
                // consultar inventario (servicios - productos)
                $inventario = ProductosServiciosFE::where('empresa_id', $payload['user']['empresa_id'])
                    ->orderBy('id')
                    ->get();

                if ($inventario->isEmpty()) {
                    return response()->json([
                        'message' => 'No se han creado productos/servicios para realizar la facturación.'
                    ], 404);
                } else {
                    // consultar informacion del abono
                    $abono = Abono::select('id', 'abono_int_mora', 'abono_gas_cobranza', 'abono_intereses')
                        ->where('id', $data['abono'])
                        ->first();

                    $datosMov = [
                        'SERVICIO DE FINANCIACIÓN' => 'abono_intereses',
                        'SERVICIO DE MORA' => 'abono_int_mora'
                    ];

                    /***************/
                    /* MOVIMIENTOS */
                    /***************/

                    foreach ($inventario as $inv) {
                        $campoAbono = $datosMov[$inv->nom_inventario] ?? null;

                        if (!$campoAbono) {
                            \Log::warning("Inventario sin mapeo en datosMov: " . $inv->nom_inventario);
                            return response()->json([
                                'message' => "Inventario sin mapeo en datosMov: " . $inv->nom_inventario
                            ], 400);
                        }

                        $dataEncabMov = [
                            "IdEncabMov" => 0,
                            "IdEncab" => $IdEncab,
                            "CantDim1" => 0,
                            "CantDim2" => 0,
                            "CantDim3" => 0,
                            "CantDim4" => 0,
                            "Cantidad" => 1,
                            "CostoPromCant" => 0,
                            "CostoPromCosto" => 0,
                            "CostoPromTipo" => 0,
                            "Dcto" => 0,
                            "EsExcluir" => false,
                            "EsVisible" => true,
                            "FactorCantidad" => 1.000000,
                            "FechaCreacion" => $fechaFE,
                            "IdBodega" => 1,
                            "IdUnidad" => 1,
                            "IdMoneda" => 1, // COP
                            "IdCliente" => $data['cliente'],
                            "IdInventario" => $inv->id_inventario,
                            "ValorIva" => 0.000000,
                            "ValorUnitario" => $abono->$campoAbono,
                            "Vence" => $fechaFE
                        ];

                        // creacion encabezado_mov
                        $encabezadoDocumentoMov = $http->post($baseUrl . 'EncabezadosMovSaveChanges', $dataEncabMov);

                        if ($encabezadoDocumentoMov->json('ErrorCode') != 0) {
                            return response()->json([
                                'message' => $encabezadoDocumentoMov->json('ErrorMessage') ?? 'Error creando el Enbezado_Mov. Intentelo de nuevo.'
                            ], 400);
                        }
                    }

                    $dataEncabCont = [
                        "IdEncabCont" => 0,
                        "IdEncab" => $IdEncab,
                        "CreditoExt" => 0,
                        "Debito" => 0,
                        "DebitoExt" => 0,
                        "EsDeducible" => false,
                        "EsIFRS" => true,
                        "EsNotGAAP" => false,
                        "EsRevisado" => false,
                        "FechaCreacion" => $fechaFE,
                        "IdCliente" => $data['cliente'],
                        "IdCuenta" => 11425,
                        "Nota" => "Factura de Venta",
                        "RetencionBase" => 0.000000,
                        "ValorTRM" => 0,
                    ];

                    // creacion encabezado_cont
                    $encabezadoDocumentoCont = $http->post($baseUrl . 'EncabezadosContSaveChanges', $dataEncabCont);

                    if ($encabezadoDocumentoCont->json('ErrorCode') != 0) {
                        return response()->json([
                            'message' => $encabezadoDocumentoCont->json('ErrorMessage') ?? 'Error creando el Enbezado_Cont. Intentelo de nuevo.'
                        ], 400);
                    }

                    // enviar factura a la DIAN
                    $sendDian = $http->post($baseUrl . "SendDocElectronico_V2?idEncab=" . $IdEncab . "&idVersion=19");

                    if ($sendDian->json('IdEstadoDian') == 38) {
                        $abono->fecha_factura_fe = Carbon::now()->format('Y-m-d H:i:s');
                        $abono->cufe_factura_fe = $sendDian->json('CUFE');
                        $abono->save();

                        return response()->json([
                            'message' => 'Documento de facturación electrónica creado y enviado a la DIAN correctamente.'
                        ], 201);
                    }

                    $mensajePrincipal = $sendDian->json('MensajeRespuesta') ?? 'Error enviando el documento a la DIAN.';
                    $mensajesValidacion = $sendDian->json('mensajesValidacion') ?? [];

                    $detalleErrores = '';

                    if (!empty($mensajesValidacion)) {
                        $detalleErrores = "\n- " . implode("\n- ", $mensajesValidacion);
                    }

                    \Log::error('DIAN rechazó el documento', [
                        'IdEncab' => $IdEncab,
                        'mensaje' => $mensajePrincipal,
                        'detalles' => $mensajesValidacion,
                    ]);

                    return response()->json([
                        'message' => $mensajePrincipal . $detalleErrores
                    ], 500);
                }
            } else {
                return response()->json([
                    'message' => 'No se pudo obtener el id del encabezado de documento de facturación electrónica.'
                ], 500);
            }

            return response()->json([
                'message' => 'Encabezado de documento de facturación electrónica creado correctamente.'
            ], 201);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Error creando el encabezado de documento de facturación electrónica: ' . $ex->getMessage()
            ], 500);
        }
    }

    public function consultarInventario()
    {
        $payload = $this->getPayload();

        $inventario = ProductosServiciosFE::where('empresa_id', $payload['user']['empresa_id'])
            ->orderBy('id')
            ->get();

        return $inventario;
    }

    public function crearProductoServicio(Request $request, FacturacionElectronicaService $feService)
    {
        $validated = $request->validate([
            'cod_inventario' => 'required|string',
            'nom_inventario' => 'required|string'
        ]);

        $payload = $this->getPayload();

        try {
            // por el momento solo crear servicio
            $data = [
                "IdInventario" => 0,
                "CodInventario" => $validated['cod_inventario'],
                "NomInventario" => $validated['nom_inventario'],
                "EsActivo" => true,
                "EsEspecial" => false,
                "CantMaxima" => 0.000000,
                "CantMinima" => 0.000000,
                "IdUnidad" => 1,
                "ValorIva" => 0,
                "IdTipoInv" => 2,
                "EsFactSinExistencia" => true,
                "EsIvaAlCosto" => false,
                "ValorUtilidadEst" => 0.000000,
                "EsSerialRequerido" => false,
                "EsFactorMov" => 1,
                "IdClasifImpto" => 1,
                "EsImptoNalCons" => false,
                "TipoImptoConsumo" => 1,
                "EsIvaIncluido" => false,
                "EsNoBalanza" => false,
                "EsControlado" => false,
                "EsPuntosAcumulados" => false,
                "EsPuntosRedimir" => false,
                "EsFactorMovSubProductos" => 0,
                "ImptoSaludableValor" => 0,
                "ImptoSaludableTipo" => 0,
                "ImptoSaludableEs" => false,
                "ImptoSaludableTotal" => 0,
                "ImptoSaludableParte" => 0,
                "EsImptoConsumoIncluido" => false,
                "EsImptoSaludIncluido" => false
            ];

            $fe = $feService->client($payload['user']['empresa_id']);
            $baseUrl = $fe['baseUrl'];
            $http = $fe['http'];

            $creacionInventario = $http->post($baseUrl . 'ServiciosSaveChanges', $data);

            if ($creacionInventario->successful() && $creacionInventario->json('IdNumber1') != 0) {
                ProductosServiciosFE::create([
                    'id_inventario' => $creacionInventario->json('IdNumber1'),
                    'empresa_id' => $payload['user']['empresa_id'],
                    'cod_inventario' => $validated['cod_inventario'],
                    'nom_inventario' => $validated['nom_inventario'],
                    'usuario_id' => $payload['user']['id'],
                    'servicio' => true,
                ]);
            } else {
                return response()->json([
                    'message' => 'El producto/servicio ya se encuentra creado.'
                ], 409);
            }

            return response()->json([
                'message' => 'Producto/servicio registrado correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Error creando el producto/servicio de facturación electrónica: ' . $ex->getMessage()
            ], 500);
        }
    }

    /*******************
     * CONFIGURACION API
     */
    public function crearConfiguracion(Request $request)
    {
        $payload = $this->getPayload();

        $data = $request->validate([
            'nombre' => 'required|string',
            'token' => 'required|string',
            'url' => 'required|string'
        ]);

        $configFE = new FacturacionElectronica();
        $configFE->empresa_id = $payload['user']['empresa_id'];
        $configFE->token = encrypt($data['token']);
        $configFE->usuario_id = $payload['user']['id'];
        $configFE->nombre = $data['nombre'];
        $configFE->url = $data['url'];
        $configFE->save();

        return response()->json([
            'configFE' => $configFE,
            'message' => 'Configuración de facturación electrónica creada correctamente.'
        ], 201);
    }

    public function eliminarConfiguracion(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $configuracion = FacturacionElectronica::find($request['id']);
        $configuracion->delete();

        return response()->json([
            'message' => 'Configuración de facturación electrónica eliminada correctamente.'
        ], 200);
    }
}
