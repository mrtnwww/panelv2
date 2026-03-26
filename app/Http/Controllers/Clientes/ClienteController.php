<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Abonos\AbonoController;
use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\CodeudorCliente;
use App\Models\Credito;
use App\Models\Empresa;
use App\Models\LineasCredito;
use App\Models\NuevaAutorizacionConsulta;
use App\Models\ParametrosEstadoFunciones;
use App\Models\Producto;
use App\Models\ProductoCliente;
use App\Models\ReferenciaCliente;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClienteController extends Controller
{
    function listMyClients(Request $request)
    {
        $usuarioId = $request->user()?->id;
        $empresaId = $request->user()?->empresa_id;

        $perPage = $request->per_page; // Número de registros por página
        $searchTerm = $request->input('search', ''); // Termino de busqueda

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
        if ($empresaUsuario && in_array(46, [
            $empresaUsuario->aliado,
            $empresaUsuario->sede
        ])) {
            $empresasAliadas->push(46);
            $aliadoImpulsa = true;
        }

        $empresasAliadas = $empresasAliadas->unique()->values();

        $clientQuery = Cliente::with(['referenciaCliente', 'ultCredito'])
            ->where('empresa_id', $empresaId)
            ->applySearch($searchTerm)
            ->applyAliado($aliado)
            ->applyOrWhereConditions($searchTerm, $empresasAliadas, $aliado, $empresaId, $tipoCliente)
            ->applyEstado($estado)
            ->applyOrigen($origen)
            ->applyResultado($resultado)
            ->applyRegistroClientes($fechaInicial, $fechaFinal);

        if ($tipoCliente == 'cliente_libranza' && $empresasAliadas->isEmpty()) $clientQuery->where('cliente_libranza', 1);

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
                $item->nombre   = '************';
                $item->email    = $encryptedEmail;
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
        $permisos =  UsuarioTipoUsuario::where('id_usuario', $usuarioId)
            ->join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
            ->select('subtipousuario.id', 'subtipousuario.nombre')
            ->get();

        // info empresa
        $empresaCliente = Empresa::find($empresaId);
        $validaciones = []; // se recolectan las validaciones que el usuario podra realizar

        $validacionParametros = ParametrosEstadoFunciones::where('empresa_id', $empresaCliente->id)
            ->whereHas('estado_funcion', function($query) {
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
            ->whereHas('estado_funcion', function($query) {
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
        $empresaId = $request->user()?->empresa_id;

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
        if ($empresaUsuario && in_array(46, [
            $empresaUsuario->aliado,
            $empresaUsuario->sede
        ])) {
            $empresas->push(46);
            $aliadoImpulsa = true;
        }

        // Obtener los clientes asociados a las empresas
        $clientes = Cliente::with([
                'credito' => function ($q) {
                    $q->whereNull('fecha_cierre')
                    ->select('id', 'client_id', 'valor_compra')
                    ->with(['proyecciones' => function ($q2) {
                        $q2->where('pagado', 0)->orderBy('fecha');
                    }]);
                },
                'credito.abonos',
                'lineaCredito:id,tipo_credito'
            ])
            ->select('id', 'nombre', 'cedula', 'empresa_id', 'cupo', 'lineas_credito_id')
            ->whereIn('empresa_id', $empresas)
            ->where('iscontinue', '!=', 1)
            ->orderBy('nombre')
            ->get();

        $clientes->transform(function ($cliente) use ($aliadoImpulsa) {
            $creditos = $cliente->credito;
            $cupo = $cliente->cupo ?? 0;
            $enMora = false;

            foreach ($creditos as $credito) {

                $cupo -= $credito->valor_compra;

                $capital = 0;

                foreach ($credito->abonos as $abono) {
                    if (!empty($abono->abono_capital)) {
                        $capital += $abono->abono_capital;
                    } else {
                        $abonosAsociados = app(AbonoController::class)->procesarAbonos($abono, true);
                        $ultimo = end($abonosAsociados) ?: [];
                        $capital += $ultimo['detalles']['capital'] ?? 0;
                    }
                }

                $cupo += $capital;

                $proyeccion = $credito->proyecciones->first();
                if ($proyeccion) {
                    $enMora = now()->gt($proyeccion->fecha);
                }
            }

            if ($creditos->isEmpty()) {
                $cupo = $cliente->cupo;
            }

            // aliado impulsa
            if ($aliadoImpulsa && $cliente->empresa_id == 46) {
                $cliente->aliadoImpulsa = true;
                $cliente->nombre = '**********';
            } else {
                $cliente->aliadoImpulsa = false;
            }

            // nuevos campos
            $cliente->enMora = $enMora;
            $cliente->numCreditos = $creditos->count();
            $cliente->cupoDisponible = $cupo < 0 ? 0 : $cupo;
            $cliente->tipoCredito = $cliente->lineaCredito->tipo_credito ?? '';

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
            }
        ])->first();

        $resultado = array(
            'cliente' => null,
            'codeudor' => null,
            'referencia' => null
        );

        if ($data) {
            $expiracion = \Carbon\Carbon::now()->addMinutes(30); // Establecer la expiración en 5 minutos

            // $data->foto_frontal = ($data->foto_frontal) ? Storage::disk('s3')->temporaryUrl($data->foto_frontal, $expiracion) : null;
            // $data->foto_posterior = ($data->foto_posterior) ? Storage::disk('s3')->temporaryUrl($data->foto_posterior, $expiracion) : null;
            // $data->foto_tarjeta = ($data->foto_tarjeta) ? Storage::disk('s3')->temporaryUrl($data->foto_tarjeta, $expiracion) : null;
            // $data->foto_tarjeta_posterior = ($data->foto_tarjeta_posterior) ? Storage::disk('s3')->temporaryUrl($data->foto_tarjeta_posterior, $expiracion) : null;
            // $data->adjuntar_aval = ($data->adjuntar_aval) ? Storage::disk('s3')->temporaryUrl($data->adjuntar_aval, $expiracion) : null;
            // $data->certificacionBancaria = ($data->certificacionBancaria) ? Storage::disk('s3')->temporaryUrl($data->certificacionBancaria, $expiracion) : null;
            // $data->debitoAutomatico = ($data->debitoAutomatico) ? Storage::disk('s3')->temporaryUrl($data->debitoAutomatico, $expiracion) : null;
            // $data->selfie = ($data->selfie) ? Storage::disk('s3')->temporaryUrl($data->selfie, $expiracion) : null;
            // $data->comprobar_cliente_externo = ($data->comprobar_cliente_externo) ? Storage::disk('s3')->temporaryUrl($data->comprobar_cliente_externo, $expiracion) : null;
            // $data->comprobar_cliente = ($data->comprobar_cliente) ? Storage::disk('s3')->temporaryUrl($data->comprobar_cliente, $expiracion) : null;
            // if ($data->url_archivo_autorizacion) {
            //     $data->url_archivo_autorizacion = ($data->url_archivo_autorizacion) ? Storage::disk('s3')->temporaryUrl($data->url_archivo_autorizacion, $expiracion) : null;
            // }

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

            $permisos =  UsuarioTipoUsuario::where('id_usuario', $usuarioId)
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
                foreach($historicoAutorizaciones as $autorizacion) {
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
                ->whereHas('estado_funcion', function($query) {
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
                $notificacionAprobacion['correo']        = $empresaCreditoPrincipal->correo;
                $notificacionAprobacion['telefono']      = $empresaCreditoPrincipal->telefonoComercial;
            }

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
                'notificacionAprobacion' => $notificacionAprobacion
            );
        }

        return response()->json([
            'resultado' => $resultado
        ]);
    }

    public function listCreditsClients()
    {
        try {
            $empresaId = auth()->user()?->empresa_id;

            // Obtener los id de las empresas
            $empresas = Empresa::where('aliado', $empresaId)
                ->orWhere('sede', $empresaId)
                ->pluck('id')
                ->toArray();

            $empresas[] = $empresaId;

            // Obtener todos los créditos de las empresas
            $creditos = Credito::whereIn('empresa_id', $empresas)
                ->with('cliente')
                ->orderBy('id', 'DESC')
                ->get();

            $resultado = $creditos->map(function ($credito) {
                $cliente = $credito->cliente;

                return [
                    'credito_id'    => $credito->id,
                    'nombre'        => $cliente->nombre,
                    'cedula'        => $cliente->cedula,
                    'cliente_id'    => $cliente->cedula,
                    'consecutivo'   => $credito->consecutivo
                ];
            });

            return response()->json([
                'clientes' => $resultado
            ]);
        } catch (\Exception $ex) {
            return response()->json(['status' => $ex->getCode(), 'message' => $ex->getMessage()], 422);
        }
    }

    public function listCreditsClientsActives()
    {
        $empresaId = auth()->user()?->empresa_id;

        // Agregar clientes con creditos de las empresas aliadas/sedes
        $empresasIds = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id')
            ->push($empresaId);

        $listaCliente = Cliente::whereIn('empresa_id', $empresasIds)
            ->whereHas('credito', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->with(['credito' => function ($q) {
                $q->whereNull('deleted_at')
                    ->select('id', 'client_id', 'valor_credito');
            }])
            ->select('id', 'cedula', 'nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'listaCliente' => $listaCliente
        ]);
    }
}
