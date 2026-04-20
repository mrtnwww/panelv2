<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Abonos\AbonoController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FirmaCliente\FirmaClienteController;
use App\Http\Controllers\Notificaciones\NotificacionController;
use App\Mail\AutorizacionConsultaCentralesDeRiesgo;
use App\Mail\ConsultaAprobada;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\CodeudorCliente;
use App\Models\ConvenioLibranza;
use App\Models\CorreosPlantilla;
use App\Models\Credito;
use App\Models\Empresa;
use App\Models\LineasCredito;
use App\Models\Notification;
use App\Models\NuevaAutorizacionConsulta;
use App\Models\PagoConsultaInfo;
use App\Models\ParametrosEstadoFunciones;
use App\Models\ParametrosInterese;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\ProductoCliente;
use App\Models\ReferenciaCliente;
use App\Models\Usuario;
use App\Models\UsuarioTipoUsuario;
use App\Models\ValidacionCuentaBancaria;
use App\Traits\ReemplazarVariablesPlantilla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;

class ClienteController extends Controller
{
    use ReemplazarVariablesPlantilla;

    function listMyClients(Request $request)
    {
        $usuarioId = $request->user()?->id;
        $empresaId = $request->user()?->empresa_id;

        $perPage = $request->per_page; // Número de registros por página
        $search = $request->input('search', ''); // Termino de busqueda

        $tipoCliente = $request->input('filtroTipoCliente', 'cliente');
        $aliado = $request->input('aliado', null);

        // Filtros checkbox
        $estado = $request['estado'];
        $origen = $request['origen'];
        $resultado = $request['resultado'];

        // filtrar por fecha de creacion de los clientes
        $fechaInicial = $request['fecha_inicial'] ?? null;
        $fechaFinal = $request['fecha_final'] ?? null;

        // Campo de ordenamiento y direccion del ordenamiento
        $sortField = $request->input('sort_key', 'cliente.id');
        $sortDirection = $request->input('sortDirection', 'desc');

        // Consulta de las empresas aliadas
        $empresasAliadas = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id');

        // validar si la empresa es aliada de CREDITRANSITO
        $empresaUsuario = Empresa::find($empresaId);
        $aliadoImpulsa = false;
        if (
            $empresaUsuario && in_array(46, [
                $empresaUsuario->aliado,
                $empresaUsuario->sede
            ])
        ) {
            $empresasAliadas->push(46);
            $aliadoImpulsa = true;
        }

        $empresasAliadas = $empresasAliadas->unique()->values();

        $clientQuery = Cliente::with(['referenciaCliente', 'ultCredito'])
            ->where('empresa_id', $empresaId)
            ->applySearch($search)
            ->applyAliado($aliado)
            ->applyOrWhereConditions($search, $empresasAliadas, $aliado, $empresaId, $tipoCliente)
            ->applyEstado($estado)
            ->applyOrigen($origen)
            ->applyResultado($resultado)
            ->applyRegistroClientes($fechaInicial, $fechaFinal);

        if ($tipoCliente == 'cliente_libranza' && $empresasAliadas->isEmpty())
            $clientQuery->where('cliente_libranza', 1);

        // Aplicar ordenamiento a los registros obtenidos de la base de datos
        $clientQuery = $sortField === 'cliente.cedula'
            ? $clientQuery->orderByRaw("CAST(cliente.cedula AS UNSIGNED) $sortDirection")
            : $clientQuery->orderBy($sortField, $sortDirection);

        // Obtener todos los ID de los clientes para utilizarlos en la seleccion de todos
        $allClientsId = $clientQuery->pluck('id');

        // Realizar paginacion
        $clientsPaginated = $clientQuery->paginate($perPage);
        $idClientesPaginated = $clientsPaginated->pluck('id');
        $creditosPorCliente = Credito::select('id', 'valor_compra')
            ->whereIn('client_id', $idClientesPaginated)
            ->get()
            ->groupBy('client_id');

        // Procesar los registros para agregar campos adicionales
        $clientsPaginated->getCollection()->transform(function ($item) use ($creditosPorCliente, $aliadoImpulsa) {
            $referencia = $item->referenciaCliente;

            $item->fecha_creacion = Carbon::parse($item->created_at)->subHours(5)->format('Y-m-d H:i:s');

            // Obtener valor del último crédito
            $item->ult_credito_valor = $item->ultCredito && !$item->ultCredito->fecha_cierre
                ? $item->ultCredito->valor_credito
                : 0;

            // data para pipeline
            // URLs temporales
            $expiracion = Carbon::now()->addMinutes(30);
            foreach (['comprobar_cliente_externo', 'foto_frontal', 'foto_posterior', 'foto_tarjeta', 'foto_tarjeta_posterior'] as $campo) {
                $temp = $campo . '_temp';
                // TODO: Configuracion S3
                // $item->$temp = $item->$campo ? Storage::disk('s3')->temporaryUrl($item->$campo, $expiracion) : null;
                $item->$temp = null;
            }

            $cupoDisponible = $item->cupo ?? 0; // Cupo aprobado del cliente
            $creditos = $creditosPorCliente->get($item->id, collect());

            foreach ($creditos as $credito) {
                $cupoDisponible -= $credito->valor_compra;

                $abonos = Abono::select('id', 'credito_id', 'abono_capital')
                    ->where('credito_id', $credito->id)
                    ->get();
                $capital = 0;

                foreach ($abonos as $abono) {
                    if (!empty($abono->abono_capital)) {
                        $capital += $abono->abono_capital ?? 0;
                    } else {
                        // Calcular capital cubierto por el abono
                        $abonosAsociados = (new AbonoController)->procesarAbonos($abono, true);

                        $ultimoAbono = end($abonosAsociados) ?: [];
                        $capital += $ultimoAbono['detalles']['capital'] ?? 0;
                    }
                }

                $cupoDisponible += $capital;
            }

            if ($aliadoImpulsa && $item->empresa_id == 46 && !$item->credito) {
                // enmascarar correo
                [$usuario, $dominio] = explode('@', $item->email);
                $encryptedEmail = $usuario[0] . str_repeat('*', 6) . substr($usuario, -1) . '@' . $dominio;

                $item->telefono = '************';
                $item->nombre = '************';
                $item->email = $encryptedEmail;
            }

            // adjunto aval
            $path = $item->adjuntar_aval;
            // TODO: Configuracion S3
            // $item->adjuntar_aval = $path ? Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(30)) : null;
            $item->adjuntar_aval = null;

            return [
                'referencia' => $referencia,
                'cliente' => $item,
                'empresa' => Empresa::find($item->empresa_id),
                'validacion_datos' => $item->cliente_validado == 1 ? true : false,
                'cupoDisponible' => $cupoDisponible
            ];
        });

        // validar si el usuario logueado es administrador
        $permisos = UsuarioTipoUsuario::where('id_usuario', $usuarioId)
            ->join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
            ->select('subtipousuario.id', 'subtipousuario.nombre')
            ->get();

        // info empresa
        $empresaCliente = Empresa::find($empresaId);
        $validaciones = []; // se recolectan las validaciones que el usuario podra realizar

        $validacionParametros = ParametrosEstadoFunciones::where('empresa_id', $empresaCliente->id)
            ->whereHas('estado_funcion', function ($query) {
                $query->where('nombre_funcion', 'Validación cliente');
            })
            ->exists();

        if ($validacionParametros) {
            // permisos del usuario que ha iniciado sesión
            $idPermisos = $permisos->pluck('id')->toArray();

            $validacionesConfig = [
                'validacion_cedula' => $empresaCliente->rol_validacion_cedula,
                'validacion_telefono' => $empresaCliente->rol_validacion_telefono,
                'validacion_referencias' => $empresaCliente->rol_validacion_referencias,
                'validacion_tarjeta_propiedad' => $empresaCliente->rol_validacion_tarjeta_propiedad,
            ];

            foreach ($validacionesConfig as $key => $rolRequerido) {
                if ($empresaCliente->$key) {
                    $validaciones[] = $key;

                    if ($rolRequerido && !in_array($rolRequerido, $idPermisos)) {
                        $validaciones = array_diff($validaciones, [$key]);
                    }
                }
            }

            $validaciones = array_values($validaciones);
        }

        // solamente el analista (o el admin) podran aprobar la consulta
        $isAnalista = $permisos->contains(function ($permiso) {
            return in_array($permiso->id, [2, 5]);
        });

        // consultar empresa principal
        $empresaPrincipal = $empresaCliente;
        if ($empresaCliente->aliado || $empresaCliente->sede) {
            $empresaPrincipal = Empresa::find($empresaCliente->aliado ?? $empresaCliente->sede);
        }

        // verificar si esta habilitada la funcion que permite que un cliente pueda tener mas de un credito a la vez
        $creditosSimultaneos = ParametrosEstadoFunciones::where('empresa_id', $empresaPrincipal->id)
            ->whereHas('estado_funcion', function ($query) {
                $query->where('nombre_funcion', 'Restringir créditos simultáneos');
            })
            ->exists();

        // consulta validacion de datos biometricos
        $datosBiometricos = ParametrosEstadoFunciones::where('empresa_id', $empresaPrincipal->id) // validacion pipeline CREDITRANSITO
            ->whereRelation('estado_funcion', 'nombre_funcion', 'Validación de datos biométricos')
            ->exists();

        $datos = [
            'clients' => $clientsPaginated,
            'allClientsId' => $allClientsId,
            'isAdmin' => $permisos->contains('id', 2),
            'validacionParametros' => $validacionParametros,
            'validaciones' => $validaciones,
            'empresaUsuario' => Empresa::select('id', 'inactivar_validacion')->find($empresaId) ?? [],
            'isAnalista' => $isAnalista,
            'aliadoImpulsa' => $aliadoImpulsa,
            'creditosSimultaneos' => $creditosSimultaneos,
            'datosBiometricos' => $datosBiometricos
        ];

        return response()->json($datos);
    }

    function listMyClientsValidated(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        // Obtener los id de las empresas aliadas/sedes
        $empresas = Empresa::where(function ($query) use ($empresaId) {
            $query->where('aliado', $empresaId)
                ->orWhere('sede', $empresaId);
        })
            ->pluck('id')
            ->push($empresaId);

        // validar si la empresa es aliada de CREDITRANSITO
        $empresaUsuario = Empresa::find($empresaId);
        $aliadoImpulsa = false;
        if (
            $empresaUsuario && in_array(46, [
                $empresaUsuario->aliado,
                $empresaUsuario->sede
            ])
        ) {
            $empresas->push(46);
            $aliadoImpulsa = true;
        }

        // Obtener los clientes asociados a las empresas
        $clientes = Cliente::select('id', 'nombre', 'cedula', 'empresa_id')
            ->whereIn('empresa_id', $empresas)
            ->where('iscontinue', '!=', 1)
            ->when($search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('cedula', 'like', "%{$search}%");
            })
            ->orderBy('nombre')
            ->paginate($perPage);

        $clientes->getCollection()->transform(function ($cliente) use ($aliadoImpulsa) {
            if ($aliadoImpulsa && $cliente->empresa_id == 46) {
                $cliente->aliadoImpulsa = true;
                $cliente->nombre = '**********';
            } else {
                $cliente->aliadoImpulsa = false;
            }

            return $cliente;
        });

        return response()->json([
            'clientes' => $clientes
        ]);
    }

    public function listMyClient(Request $request)
    {
        $usuarioId = $request->user()?->id;
        $empresaId = $request->user()?->empresa_id;

        $clienteId = $request['cliente_id'];

        $data = Cliente::where('id', $clienteId)
            ->with([
                'firma_cliente' => function ($query) {
                    $query->with([
                        'documentos' => function ($subquery) {
                            $subquery->with('adjunto');
                        }
                    ]);
                },
                'ciudad'
            ])
            ->first();

        $resultado = array(
            'cliente' => null,
            'codeudor' => null,
            'referencia' => null
        );

        if ($data) {
            $camposArchivos = [
                'foto_frontal',
                'foto_posterior',
                'foto_tarjeta',
                'foto_tarjeta_posterior',
                'adjuntar_aval',
                'certificacionBancaria',
                'debitoAutomatico',
                'selfie',
                'comprobar_cliente_externo',
                'comprobar_cliente',
                'url_archivo_autorizacion',
            ];

            /**
             * Indica si el valor del campo corresponde a un archivo real.
             */
            $tieneArchivo = fn($path): bool => !in_array((string) $path, ['0', 'x', '', null], strict: true);

            $expiracion = now()->addMinutes(30);
            $disk = Storage::disk('s3');

            foreach ($camposArchivos as $campo) {
                $path = $data->{$campo} ?? null;

                if ($tieneArchivo($path)) {
                    try {
                        $data->{$campo} = $disk->temporaryUrl($path, $expiracion);
                    } catch (\Exception $ex) {
                        // El archivo existe en BD pero no en storage (inconsistencia)
                        $data->{$campo} = null;
                    }
                } else {
                    $data->{$campo} = null;
                }
            }

            $productoCliente = ProductoCliente::where('id_cliente', $data->id)->get();
            $listaProductos = array();
            foreach ($productoCliente as $item) {
                $listaProductos[] = Producto::where('id', $item->id_producto)->select('nombre', 'precio')->first();
            }

            // Validar si la empresa del usuario logueado es un aliado o una sede
            $empresaLogin = Empresa::select('sede', 'aliado')
                ->where('id', $empresaId)
                ->first();
            $aliado = (is_null($empresaLogin->sede) && is_null($empresaLogin->aliado)) ? false : true;

            $permisos = UsuarioTipoUsuario::where('id_usuario', $usuarioId)
                ->join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
                ->select('subtipousuario.id', 'subtipousuario.nombre')
                ->get();

            $destinoCredito = LineasCredito::withTrashed()->find($data->lineas_credito_id);

            // historico de autorizaciones de consulta realizadas
            $autorizaciones = [];

            // consulta de nuevas autorizaciones realizadas por vencimiento de la vigencia de la consulta en centrales
            $historicoAutorizaciones = NuevaAutorizacionConsulta::where('cliente_id', $data->id)
                ->orderBy('id', 'desc')
                ->get();

            if (!empty($historicoAutorizaciones)) {
                foreach ($historicoAutorizaciones as $autorizacion) {
                    $url = $autorizacion->url_archivo_autorizacion;
                    $fechaAutorizacion = Carbon::parse($autorizacion->created_at)->format('d/m/Y');

                    // $url = $url ? Storage::disk('s3')->temporaryUrl($url, $expiracion) : null;

                    $autorizaciones[] = [
                        'url' => $url,
                        'fechaAutorizacion' => $fechaAutorizacion
                    ];
                }
            }

            // primera autorizacion a centrales firmada por el cliente
            $autorizaciones[] = [
                'url' => $data->url_archivo_autorizacion,
                'fechaAutorizacion' => Carbon::parse($data->firmado)->format('d/m/Y')
            ];

            // solamente el analista (o el admin) podran modificar el cupo del cliente
            $isAnalista = $permisos->contains(function ($permiso) {
                return in_array($permiso->id, [2, 5]);
            });

            // info empresa
            $empresaCliente = Empresa::find($empresaId);
            $validaciones = []; // se recolectan las validaciones que el usuario podra realizar

            $validacionParametros = ParametrosEstadoFunciones::where('empresa_id', $empresaCliente->id)
                ->whereHas('estado_funcion', function ($query) {
                    $query->where('nombre_funcion', 'Validación cliente');
                })
                ->exists();

            if ($validacionParametros) {
                // permisos del usuario que ha iniciado sesión
                $idPermisos = $permisos->pluck('id')->toArray();

                $validacionesConfig = [
                    'validacion_cedula' => $empresaCliente->rol_validacion_cedula,
                    'validacion_cuenta' => $empresaCliente->rol_validacion_cuenta,
                    'validacion_telefono' => $empresaCliente->rol_validacion_telefono,
                    'validacion_referencias' => $empresaCliente->rol_validacion_referencias,
                    'validacion_tarjeta_propiedad' => $empresaCliente->rol_validacion_tarjeta_propiedad,
                ];

                foreach ($validacionesConfig as $key => $rolRequerido) {
                    if ($empresaCliente->$key) {
                        $validaciones[] = $key;

                        if ($rolRequerido && !in_array($rolRequerido, $idPermisos)) {
                            $validaciones = array_diff($validaciones, [$key]);
                        }
                    }
                }

                $validaciones = array_values($validaciones);
            }

            // ofuscacion de la cuenta bancaria retornada al cliente
            if (!empty($data->num_cuenta_bancaria)) {
                $cuenta = decrypt($data->num_cuenta_bancaria);

                // if (strlen($cuenta) >= 5) {
                //     $encryptedAccount =
                //         substr($cuenta, 0, 2) .
                //         str_repeat('*', strlen($cuenta) - 5) .
                //         substr($cuenta, -3);
                // } else {
                //     $encryptedAccount = '*****';
                // }

                // $data->num_cuenta_bancaria = $encryptedAccount;
                $data->num_cuenta_bancaria = $cuenta;
            }

            $camposEmpresa = ['id', 'razon_social', 'telefonoComercial', 'correo', 'sede', 'aliado'];
            $notificacionAprobacion = [];

            // empresa donde se solicito el credito
            $empresaCredito = Empresa::select($camposEmpresa)
                ->find($data->empresa_id);

            if ($empresaCredito) {
                // validar si la empresa es un aliado o una sede
                $empresaPrincipalId = $empresaCredito->aliado ?
                    $empresaCredito->aliado : $empresaCredito->sede;

                $empresaCreditoPrincipal = $empresaPrincipalId
                    ? Empresa::select($camposEmpresa)->find($empresaPrincipalId)
                    : $empresaCredito;

                $notificacionAprobacion['nombreEmpresa'] = $empresaCredito->razon_social;
                $notificacionAprobacion['correo'] = $empresaCreditoPrincipal->correo;
                $notificacionAprobacion['telefono'] = $empresaCreditoPrincipal->telefonoComercial;
            }

            // Departamento-Ciudad
            $ciudad = $data->ciudad()->first();
            $departamento = $ciudad?->departamento;

            $ciudad = ($ciudad && $departamento)
                ? "{$departamento->nombre}-{$ciudad->nombre}"
                : null;

            $resultado = array(
                'cliente' => $data,
                'codeudor' => CodeudorCliente::where('cedula_cliente_id', $request['cedula'])->get(),
                'referencia' => ReferenciaCliente::where('cliente_id', $data->id)->first(),
                'productos' => $listaProductos,
                'isAdmin' => $permisos->contains('id', 2),
                'isAnalista' => $isAnalista,
                'esAliado' => $aliado,
                'destinoCredito' => $destinoCredito,
                'autorizaciones' => $autorizaciones ?? [],
                'validacionParametros' => $validacionParametros,
                'validaciones' => $validaciones,
                'empresaUsuario' => Empresa::select('id', 'inactivar_validacion')->find($empresaId) ?? [],
                'notificacionAprobacion' => $notificacionAprobacion,
                'ciudad' => $ciudad
            );
        }

        return response()->json([
            'resultado' => $resultado
        ]);
    }

    public function listClientsCredits(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);

        $empresas = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id');

        $empresas->push($empresaId);

        $clientes = Cliente::query()
            ->select(['cliente.id', 'cliente.nombre', 'cliente.cedula', 'cliente.empresa_id'])
            ->whereHas('credito', function ($q) use ($empresas) {
                $q->whereIn('empresa_id', $empresas);
            })
            ->with([
                'empresa:id,razon_social',
                'credito' => function ($q) use ($empresas) {
                    $q->select([
                        'credito.id',
                        'credito.client_id',
                        'credito.valor_credito',
                        'credito.consecutivo',
                    ])
                        ->whereIn('empresa_id', $empresas);
                }
            ])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('cliente.nombre', 'like', "%{$search}%")
                        ->orWhere('cliente.cedula', 'like', "%{$search}%");
                });
            })
            ->orderBy('cliente.nombre')
            ->paginate($perPage);

        return response()->json(compact('clientes'));
    }

    public function generarArchivoAutorizacion($clienteData, $regenerarAutorizacion = false, $texto = '')
    {
        $empresa = Empresa::find($clienteData->empresa_id);
        $cliente = Cliente::find($clienteData->id);

        // fecha y hora actuales en las que se acepta la consulta
        $fechaFirma = !$regenerarAutorizacion ? now() : null;

        if (!empty($texto)) {
            $texto = preg_replace('/\x{FEFF}+/u', '', $texto);
            $texto = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $texto);
            $pdf = \PDF::loadView('pdf.autorizacionPlantilla', compact('cliente', 'texto', 'fechaFirma'));
        } else {
            $pdf = \PDF::loadView('pdf.autorizacion', compact('cliente', 'empresa', 'fechaFirma'));
        }
        $fileContent = $pdf->output();

        $nombreArchivo = uniqid() . 'autorizacion.pdf';
        $path = 'public/' . $nombreArchivo;

        $guardado = Storage::disk('s3')->put($path, $fileContent);

        if ($guardado) {
            if ($cliente->nueva_autorizacion_consulta == 1) {
                // guardar autorizacion en tabla intermedia (unicamente para clientes antiguos a los cuales se les realiza reconsulta en centrales)
                NuevaAutorizacionConsulta::create([
                    'cliente_id' => $cliente->id,
                    'url_archivo_autorizacion' => $path
                ]);

                // confirmar que la nueva autorizacion ya se ha generado y guardado en la tabla intermedia
                $cliente->update(['nueva_autorizacion_consulta' => 0]);
            } else {
                // si nunca se ha generado el archivo de autorizacion, se genera por primera vez
                $cliente->url_archivo_autorizacion = $path;
                $cliente->update();
            }
        }
    }

    public function updateCliente(Request $request)
    {
        return $this->saveCliente($request);
    }

    public function createCliente(Request $request)
    {
        return $this->saveCliente($request);
    }

    private function saveCliente(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $usuarioId = auth()->user()->id;

        $response = DB::transaction(function () use ($request, $empresaId, $usuarioId) {
            $id = $request->input('id', null);

            $empresa = Empresa::find($empresaId);

            $documentosEnviar = null;
            $clientExists = false;

            if ($id) {
                // Buscar cliente
                $cliente = Cliente::findOrFail($id);
                $empresa = Empresa::findOrFail($cliente->empresa_id);
                $clientExists = true;
            }

            if (!$clientExists) {
                // Instanciar cliente
                $cliente = new Cliente();

                $cliente->token = uniqid();
                $cliente->empresa_id = $empresa->id;

                // Generar OTP único solo para nuevos
                do {
                    $otp = rand(100000, 999999);
                } while (Cliente::where('otp_creacion_cliente', $otp)->exists());
                $cliente->otp_creacion_cliente = $otp;

                // Documentos para firmar
                $documentosEnviar = $request['documentosEnviar'];
            }

            // 3. Asignación de datos comunes (Update y Create)
            $cliente->fill([
                'cedula' => $request['cedula'],
                'nombre' => $request['nombre'],
                'telefono' => $request['telefono'],
                'email' => $request['correo'],
                'fecha_nacimiento' => $request['fechaNacimiento'],
                'direccion' => $request['direccion'],
                'barrio' => $request['barrio'],
                'ciudad' => $request['ciudad'],
                'salario' => $request['salario'] ?? 0,
                'empresa_labora' => $request['nombreEmpleador'],
                'telEmpresa' => $request['telefonoEmpleador'],
                'direccionEmpresa' => $request['direccionEmpleador'],
                'cupo' => $request['cupo'],
                'iscontinue' => $request['continuarproceso'] ?? false,
                'user_id' => $usuarioId,
                'obs_validacion' => $request['obs_validacion'],
                'foto_frontal_validada' => $request['validar_cedula_frontal'],
                'foto_posterior_validada' => $request['validar_cedula_posterior'],
                'foto_tarjeta_validada' => $request['validar_tarjeta_frontal'],
                'num_cuenta_bancaria_validada' => $request['validar_num_cuenta_bancaria'],
                'foto_tarjeta_posterior_validada' => $request['validar_tarjeta_posterior'],
                'telefono_validado' => $request['telefono_validado'],
                'tipo_cuenta_bancaria' => $request['tipo_cuenta_bancaria'],
                'nombre_banco' => $request['nombre_banco'],
                'tipo_empleado' => 'empleado',
            ]);

            if ($request['continuarproceso'] != 0)
                $cliente->obsproceso = $request['obsproceso'];

            // Cuenta bancaria y aval
            if (isset($request['num_cuenta_bancaria']) && preg_match('/^[0-9]+$/', $request['num_cuenta_bancaria'])) {
                $cliente->num_cuenta_bancaria = encrypt($request['num_cuenta_bancaria']);
            }

            if ($request['analisisEstado'] || $request->hasFile('analisisDoc')) {
                $cliente->no_aval = $request['analisisNumeroConsulta'];
                $cliente->nota = $request['analisisNota'];
                $cliente->estado_aval = $request['analisisEstado'];
                $cliente->notificarAval = $request['analisisEstado'] ? 1 : 0;

                if ($request->hasFile('analisisDoc')) {
                    $path = $cliente->cedula . "/aval/";
                    $cliente->adjuntar_aval = $this->procesarGuardarArchivo($request->file('analisisDoc'), $path);
                }

                if ($cliente->estado_aval == 1) {
                    /**
                     * Si el aval del cliente (análisis de consulta en centrales) es 1
                     * se creará una nueva notificación para los usuarios ASESOR (3)
                     * informando que el cliente cuenta con análisis de consulta y fue
                     * aprobado para crédito.
                     */

                    (new NotificacionController)->newNotification([
                        'client_id' => $cliente->id,
                        'empresa_id' => $empresaId,
                        'user_id' => $usuarioId,
                        'title' => 'NUEVO CLIENTE CON ANÁLISIS DE EN CENTRALES DE RIESGO',
                        'content' => 'Se ha efectuado el ánalisis en centrales de riesgo de ' . $cliente->nombre . ' y ha sido aprobado.',
                        'url' => '/clients/details/' . base64_encode($cliente->cedula . ';;' . $cliente->id),
                        'type' => 'CLIENT_ANALIZED'
                    ], 3);
                }
            }

            // Guardar archivos (cédula, tarjeta propiedad, certificación bancaria, debito automático, foto cliente)
            $archivos = [
                'fotoCliente' => 'comprobar_cliente',
                'cedulaFront' => 'foto_frontal',
                'cedulaBack' => 'foto_posterior',
                'tarjetaPropiedadFront' => 'foto_tarjeta',
                'tarjetaPropiedadBack' => 'foto_tarjeta_posterior',
                'autorizacionDebitoDoc' => 'debitoAutomatico',
                'certBancaria' => 'certificacionBancaria'
            ];

            foreach ($archivos as $archivo => $column) {
                if ($request->hasFile($archivo)) {
                    $cliente[$column] = $this->procesarGuardarArchivo($request->file($archivo)) ?? null;
                }
            }

            // 4. Crear nuevo cliente
            if (!$clientExists) {
                $this->preSaveCliente($cliente, $empresa, $request);
            }

            // Guardar o actualizar cliente
            $cliente->save();

            // 5. Acciones post nuevo cliente
            if (!$clientExists) {
                $this->postSaveCliente($cliente, $request, $empresa, $documentosEnviar, $empresaId, $usuarioId);
            }

            // Validación cuenta bancaria
            if (
                $request['validar_num_cuenta_bancaria'] == 1 &&
                !empty($cliente->num_cuenta_bancaria)
            ) {
                $cuentaCliente = decrypt($cliente->num_cuenta_bancaria);

                $validacion = ValidacionCuentaBancaria::where('cliente_id', $cliente->id)
                    ->latest()
                    ->first();

                $requiereValidacion = !$validacion
                    || decrypt($validacion->num_cuenta) != $cuentaCliente;

                if ($requiereValidacion) {
                    ValidacionCuentaBancaria::create([
                        'estado' => 'validado',
                        'cliente_id' => $cliente->id,
                        'num_cuenta' => $cliente->num_cuenta_bancaria,
                        'usuario_id' => $usuarioId
                    ]);
                }
            }

            // Archivo autorización consulta
            if ($request->hasFile('autorizacionCentralesDoc')) {
                $urlArchivoAutorizacion = $this->procesarGuardarArchivo($request->file('autorizacionCentralesDoc')) ?? null;

                if ($cliente->nueva_autorizacion_consulta == 0) {
                    $cliente->update([
                        'url_archivo_autorizacion' => $urlArchivoAutorizacion,
                        'aprobar_autorizacion' => 1,
                        'autorizacion' => 1,
                        'firmado' => Carbon::now(),
                        'token' => null,
                    ]);
                } else {
                    // guardar autorizacion en tabla intermedia (unicamente para clientes antiguos a los cuales se les realiza reconsulta en centrales)
                    NuevaAutorizacionConsulta::create([
                        'url_archivo_autorizacion' => $urlArchivoAutorizacion,
                        'cliente_id' => $cliente->id
                    ]);

                    // confirmar que la nueva autorizacion ya se ha generado y guardado en la tabla intermedia
                    $cliente->update([
                        'nueva_autorizacion_consulta' => 0,
                        'aprobar_autorizacion' => 1,
                        'autorizacion' => 1,
                        'token' => null,
                    ]);
                }
            }

            // Referencias
            $referencia = null;
            if (!empty($request['referencias'])) {
                $referencias = collect($request['referencias']);

                // Definimos el mapa de cómo se distribuyen los índices a las columnas
                $mapa = [
                    0 => ['ref' => 'ref_comecial_1', 'res' => 'res_ref_comecial_1', 'tel' => 'tel_1', 'com' => 'com_1'],
                    1 => ['ref' => 'ref_comecial_2', 'res' => 'res_ref_comecial_2', 'tel' => 'tel_2', 'com' => 'com_2'],
                    2 => ['ref' => 'ref_familiar_1', 'res' => 'res_ref_familiar_1', 'tel' => 'tel_3', 'com' => 'com_3'],
                    3 => ['ref' => 'ref_familiar_2', 'res' => 'res_ref_familiar_2', 'tel' => 'tel_4', 'com' => 'com_4'],
                ];

                $data = [];
                foreach ($mapa as $index => $columnas) {
                    $refData = $referencias->get($index);

                    $data[$columnas['ref']] = $refData['nombre'] ?? null;
                    $data[$columnas['res']] = null;
                    $data[$columnas['tel']] = $refData['telefono'] ?? null;
                    $data[$columnas['com']] = $refData['nota'] ?? null;
                }

                $referencia = ReferenciaCliente::updateOrCreate(
                    ['cliente_id' => $cliente->id],
                    $data
                );
            }

            // Si el cliente no ha sido validado, se procede a la validacion
            if ($cliente->cliente_validado == 0)
                $this->validarCliente($cliente, $referencia);

            // Si el cliente se encuentra recientemente validado, se muestra la notificacion
            if ($cliente->cliente_validado == 1) {
                (new NotificacionController)->newNotification([
                    'client_id' => $cliente->id,
                    'empresa_id' => $empresaId,
                    'user_id' => $usuarioId,
                    'title' => 'NUEVO CLIENTE VALIDADO',
                    'content' => $cliente->nombre . ' ha sido validado y requiere análisis en centrales de riesgo.',
                    'url' => '/clients/details/' . base64_encode($cliente->cedula . ';;' . $cliente->id),
                    'type' => 'CLIENT_VALIDATED'
                ], 5);
            }

            return response()->json([
                'message' => $clientExists ? 'Cliente actualizado exitosamente' : 'Cliente creado exitosamente',
                'cliente_id' => $cliente->id
            ]);
        });

        return $response;
    }

    private function preSaveCliente(&$cliente, $empresa, $request)
    {
        // Lógica de líneas de crédito, pago consulta, etc.
        $empresaPrincipal = ($empresa->aliado || $empresa->sede)
            ? Empresa::find($empresa->aliado ?? $empresa->sede)
            : $empresa;

        $ocultarOrdinario = ParametrosEstadoFunciones::where('empresa_id', $empresaPrincipal->id)
            ->whereHas('estado_funcion', fn($q) => $q->where('nombre_funcion', 'Ocultar línea de crédito ordinario'))
            ->exists();

        $lineaId = 1;
        if ($ocultarOrdinario) {
            $linea = LineasCredito::where('empresa_id', $empresaPrincipal->id)->orderBy('id')->first();
            $lineaId = $linea->id ?? 1;
        }

        $cliente->lineas_credito_id = $lineaId;

        $paramInt = ParametrosInterese::where('empresa_id', $empresa->id)
            ->where('lineas_credito_id', $lineaId)->first();

        $infoPago = new PagoConsultaInfo(['valor_pagar' => $paramInt->valor_consulta ?? 0]);
        $infoPago->save();
        $cliente->pago_consulta_info_id = $infoPago->id;

        // Fecha de visualización formulario
        $cliente->fecha_visualizacion_formulario = $request['fechaVisualizacionFormulario'] ?
            Carbon::parse($request['fechaVisualizacionFormulario']) :
            null;
    }

    private function postSaveCliente($cliente, $request, $empresa, $documentosEnviar, $empresaId, $usuarioId)
    {
        $persona = Persona::create([
            'nombre' => $request['nombre'],
            'direccion' => $request['direccion'],
            'contacto' => $request['telefono'],
            'ciudad_id' => $request['ciudad']
        ]);

        Usuario::create([
            'correo' => $request['cedula'],
            'password' => Hash::make($request['cedula']),
            'subtipousuario_id' => 7,
            'persona_id' => $persona->id,
            'empresa_id' => $empresa->id,
            'client_id' => $cliente->id
        ]);

        // Notificación Nuevo Cliente
        (new NotificacionController)->newNotification([
            'client_id' => $cliente->id,
            'empresa_id' => $empresaId,
            'user_id' => $usuarioId,
            'title' => 'NUEVO CLIENTE CREADO',
            'content' => $cliente->nombre . ' ha sido creado.',
            'type' => 'CLIENT_CREATED'
        ], ['whereType' => [3, 4], 'whereId' => []]);

        // Enviar documentos a firmar
        if (!empty($documentosEnviar)) {
            FirmaClienteController::crear($cliente->id, $empresaId, $documentosEnviar);
        }
    }

    public function envioComunicacionConsultaAprobada($cliente, $empresa = null)
    {
        if (!$empresa)
            $empresa = Empresa::find($cliente->empresa_id);
        $empresaPrincipal = Empresa::find($empresa->aliado ?? $empresa->sede ?? $empresa->id);

        if ($empresaPrincipal->id == 46 || $empresaPrincipal->id == 118) {
            $datos = [
                'telefonoComercial' => $empresaPrincipal->telefonoComercial,
                'correoComercial' => $empresaPrincipal->correo_comercial,
                'empresaPrincipal' => $empresaPrincipal->razon_social,
                'empresa' => $empresa->razon_social,
                'cliente' => $cliente->nombre,
            ];

            if ($cliente->email) {
                try {
                    Mail::to($cliente->email)->send(new ConsultaAprobada($datos));
                } catch (\Throwable $mailException) {
                    \Log::error('Error enviando correo aprobación consulta', [
                        'email' => $cliente->email,
                        'error' => $mailException->getMessage()
                    ]);
                }
            }
        }
    }

    private function procesarGuardarArchivo($archivo, $path = 'public/')
    {
        try {
            $mime = $archivo->getClientMimeType();

            // optimizar imagen
            if (in_array($mime, ['image/jpeg', 'image/png'])) {
                $optimizerChain = OptimizerChainFactory::create();

                if ($mime === 'image/jpeg') {
                    $optimizerChain->addOptimizer(new Jpegoptim([
                        '-m85',
                        '--strip-all',
                        '--all-progressive',
                    ]));
                } else if ($mime === 'image/png') {
                    $optimizerChain->addOptimizer(new Pngquant([
                        '--quality=80-100',
                        '--speed=1',
                    ]));
                    $optimizerChain->addOptimizer(new Optipng([
                        '-i0',
                        '-o2',
                        '-quiet',
                    ]));
                }

                $optimizerChain->optimize($archivo->getRealPath());
            }

            $nombreLimpio = str_replace(' ', '_', $archivo->getClientOriginalName());
            $nombreArchivo = uniqid() . $nombreLimpio;

            return Storage::disk('s3')->putFile($path . $nombreArchivo, $archivo);
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function validarCliente($cliente, $referencias)
    {
        $empresaId = auth()->user()->empresa_id;
        $empresaPrincipal = Empresa::find($empresaId);

        $validacionParametros = ParametrosEstadoFunciones::where('empresa_id', $empresaPrincipal->id)
            ->whereHas('estado_funcion', function ($query) {
                $query->where('nombre_funcion', 'Validación cliente');
            })
            ->exists();

        $validacionCedula = $validacionParametros ? $empresaPrincipal->validacion_cedula : 0;
        $validacionTelefono = $validacionParametros ? $empresaPrincipal->validacion_telefono : 0;
        $validacionReferencias = $validacionParametros ? $empresaPrincipal->validacion_referencias : 0;
        $validacionTarjeta = $validacionParametros ? $empresaPrincipal->validacion_tarjeta_propiedad : 0;

        $parametrosCheckeados = $validacionCedula == 1
            || $validacionTarjeta == 1
            || $validacionTelefono == 1
            || $validacionReferencias == 1;

        $telefonoValido = ($cliente->telefono_validado == 1);
        $cedulaValida = ($cliente->foto_frontal_validada == 1 && $cliente->foto_posterior_validada == 1);
        $tarjetaValida = ($cliente->foto_tarjeta_validada == 1 && $cliente->foto_tarjeta_posterior_validada == 1);
        $referenciasValidas = false;

        if ($referencias) {
            $referenciasValidas = $referencias->res_ref_comecial_1 == 1 ||
                $referencias->res_ref_comecial_2 == 1 ||
                $referencias->res_ref_familiar_1 == 1 ||
                $referencias->res_ref_familiar_2 == 1;
        }

        // flag para determinar si el cliente se encuentra correctamente validado
        $clienteValidado = true;

        if ($validacionCedula == 1 && !$cedulaValida)
            $clienteValidado = false;
        if ($validacionTarjeta == 1 && !$tarjetaValida)
            $clienteValidado = false;
        if ($validacionTelefono == 1 && !$telefonoValido)
            $clienteValidado = false;
        if ($validacionReferencias == 1 && !$referenciasValidas)
            $clienteValidado = false;

        // si la funcion esta activa y si al menos uno de los parametros de validacion esta checkeado
        if ($validacionParametros && $parametrosCheckeados) {
            $valor = $clienteValidado ? 1 : 0; // comprobar que el cliente este validado
        } else {
            $valor = $referenciasValidas ? 1 : 0; // si no solo comprobar que se hayan validado las referencias
        }

        $notification = Notification::where('client_id', $cliente->id) // busqueda por cliente
            ->where('type', 'CLIENT_VALIDATED')
            ->orderBy('id', 'desc')
            ->first();

        if ($notification && $valor == 0) {
            $notification->delete();
        }

        $cliente->update(['cliente_validado' => $valor]);
    }

    public function reenviarAutorizacion(Request $request)
    {
        $empresaid = auth()->user()->empresa_id;

        $correo = $request->input('correo', '');
        $idCliente = $request->input('id', null);

        $cliente = Cliente::find($idCliente);

        if ($cliente) {
            // limpiar los campos vinculados a la autorizacion
            $cliente->update([
                'aprobar_autorizacion' => 0,
                'autorizacion' => 0
            ]);

            // si aun no se ha aprobado la nueva consulta en centrales, las nuevas autorizaciones se guardaran en la tabla intermedia [nueva_autorizacion_consulta]
            if ($cliente->nueva_consulta_centrales == 1)
                $cliente->update(['nueva_autorizacion_consulta' => 1]);

            $empresa = Empresa::where('id', $empresaid)->first();

            // Validar si la empresa es un aliado o una sede
            if ($empresa->aliado || $empresa->sede) {
                $empresa = Empresa::where('id', $empresa->aliado ?? $empresa->sede)->first();
            }

            $empresa->logoEmpresa = ($empresa->logo) ? Storage::url($empresa->logo) : '';

            $url = '';

            $plantilla = CorreosPlantilla::where('empresa_id', $empresa->id)
                ->where('nombre', 'Autorización de consulta y reporte en centrales')
                ->first();

            $texto = '';
            $asunto = '';
            if ($plantilla) {
                $clienteLibranza = $cliente->clienteLibranza;

                if ($clienteLibranza) {
                    $convenio = ConvenioLibranza::find($clienteLibranza->convenio_empresa_id);

                    if ($convenio) {
                        $texto = $this->reemplazarVariablesPlantillaCorreo($plantilla->texto, $convenio, $empresa, $cliente);
                        $asunto = $plantilla->asunto;
                    }
                }
            }

            if (isset($cliente->token)) {
                $random = $cliente->token;
                Cliente::where('id', $idCliente)->update(['email' => $correo]);
                Mail::to($correo)->send(new AutorizacionConsultaCentralesDeRiesgo($random, $cliente, $empresa, $url, $texto, $asunto));
            } else {
                $random = md5(rand(1, 500000));
                Cliente::where('id', $idCliente)->update(['email' => $correo, 'token' => $random]);
                Mail::to($correo)->send(new AutorizacionConsultaCentralesDeRiesgo($random, $cliente, $empresa, $url, $texto, $asunto));
            }

            return response()->json([
                'message' => 'Correo enviado correctamente.'
            ]);
        } else {
            return response()->json([
                'message' => 'El cliente no se encuentra aún registrado.'
            ], 404);
        }
    }
}
