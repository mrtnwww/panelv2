<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Empresa;
use App\Models\ParametrosEstadoFunciones;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClienteController extends Controller
{
    function listMyClients(Request $request)
    {
        $user = auth()->user();

        $userId    = $user->id;
        $empresaId = $user->empresa_id;

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
                        // TODO: Pendiente de crear controlador CreditController
                        // $abonosAsociados = (new CreditController)->procesarAbonos($abono, true);

                        // $ultimoAbono = end($abonosAsociados) ?: [];
                        // $capital += $ultimoAbono['detalles']['capital'] ?? 0;
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
        $permisos =  UsuarioTipoUsuario::where('id_usuario', $userId)
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
}
