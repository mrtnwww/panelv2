<?php

namespace App\Http\Controllers\Tareas;

use App\Http\Controllers\Controller;
use App\Models\Credito;
use App\Models\Tarea;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TareaController extends Controller
{
    function listTasks(Request $request)
    {
        $usuario = $request->user();

        $currentUserId = $usuario->id;
        $empresaId = $usuario->empresa_id;

        $hoy = Carbon::today();

        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');

        // Filtros de busqueda
        $conditions = [
            'filtroEstado' => $request->input('tab', ''),
            'creacion' => [
                'desde' => $request->input('creacion_desde', ''),
                'hasta' => $request->input('creacion_hasta', ''),
            ],
            'vencimiento' => [
                'desde' => $request->input('vencimiento_desde', ''),
                'hasta' => $request->input('vencimiento_hasta', ''),
            ],
            'completada' => [
                'desde' => $request->input('completada_desde', ''),
                'hasta' => $request->input('completada_hasta', ''),
            ],
            'cliente' => $request->input('cliente_id', ''),
            'usuario' => $request->input('usuario_id', ''),
            'tipo' => $request->input('tipo_tarea', '')
        ];

        // verificar roles
        $roles = UsuarioTipoUsuario::where('id_usuario', $currentUserId)
            ->whereIn('id_tipo_usuario', [2, 6])
            ->pluck('id_tipo_usuario');

        $isAdmin = $roles->contains(2);
        $isCobranza = $roles->contains(6);

        $hasTasks = $isCobranza ?
            Tarea::where('usuario_asignado', $currentUserId)
                ->where('completado', 0)
                ->exists()
            : false;

        $tieneTareas = $isAdmin || $hasTasks;

        $tareaMasImportante = Tarea::where('usuario_asignado', $currentUserId)
            ->whereNotNull('client_id')
            ->where('completado', 0)
            ->join('cliente', 'cliente.id', '=', 'tareas.client_id')
            ->orderBy('fecha_vencimiento', 'asc')
            ->select('tareas.*', 'cliente.nombre')
            ->first();

        $baseQuery = Tarea::where('tareas.empresa_id', $empresaId)
            ->join('usuario', 'usuario.id', '=', 'tareas.usuario_asignado')
            ->join('persona', 'persona.id', '=', 'usuario.persona_id')
            ->leftJoin('cliente', 'cliente.id', '=', 'tareas.client_id')
            ->applySearch($searchTerm)
            ->applyConditions($conditions);

        if (!$isAdmin)
            $baseQuery->where('tareas.usuario_asignado', $currentUserId);

        $allTareasIds = (clone $baseQuery)
            ->select('tareas.id')
            ->pluck('tareas.id')
            ->toArray();

        // tareas paginadas
        $tareas = (clone $baseQuery)
            ->select([
                'tareas.*',
                'persona.nombre',
                'cliente.nombre AS cliente_nombre'
            ])
            ->orderBy('tareas.id', 'desc')
            ->paginate($perPage);

        // totales
        $totales = (clone $baseQuery)
            ->select(DB::raw("
                COUNT(*) as totales,
                COALESCE(SUM(CASE WHEN fecha_completado IS NOT NULL THEN 1 ELSE 0 END), 0) as completadas,
                COALESCE(SUM(CASE WHEN fecha_completado IS NULL AND fecha_vencimiento IS NOT NULL AND DATE(fecha_vencimiento) < '{$hoy->toDateString()}' THEN 1 ELSE 0 END), 0) as vencidos,
                COALESCE(SUM(CASE WHEN fecha_completado IS NULL AND fecha_vencimiento IS NOT NULL AND DATE(fecha_vencimiento) = '{$hoy->toDateString()}' THEN 1 ELSE 0 END), 0) as vencenHoy,
                COALESCE(SUM(CASE WHEN fecha_completado IS NULL AND fecha_vencimiento IS NOT NULL AND DATE(fecha_vencimiento) > '{$hoy->toDateString()}' THEN 1 ELSE 0 END), 0) as proximos
            "))
            ->first();

        return response()->json([
            'tareas' => $tareas,
            'tieneTareas' => $tieneTareas,
            'idUsuario' => base64_encode($currentUserId),
            'tareaMasImportante' => $tareaMasImportante,
            'isAdmin' => $isAdmin,
            'totales' => $totales,
            'allTareasIds' => $allTareasIds
        ]);
    }

    public function createTarea(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'tarea.creditosId' => 'required',
            'tarea.nota' => 'required',
            'tarea.titulo' => 'required',
            'tarea.prioridad' => 'required',
            'tarea.tipoTarea' => 'required',
            'tarea.vencimiento' => 'required',
            'tarea.usuarioAsignado' => 'required',
        ]);

        $inputs = $data['tarea'];

        $creditosId = collect(json_decode($inputs['creditosId']));

        // Obtener clientes únicos
        $clienteIds = Credito::whereIn('id', $creditosId)
            ->pluck('client_id')
            ->unique();

        $fechaVencimiento = $inputs['vencimiento'] ?? now();
        $nuevasTareas = [];

        foreach ($clienteIds as $clienteId) {
            $nuevasTareas[] = [
                'empresa_id' => $user->empresa_id,
                'user_id' => $user->id,
                'client_id' => $clienteId,
                'notas' => $inputs['nota'],
                'titulo' => $inputs['titulo'],
                'tipo' => $inputs['tipoTarea'],
                'prioridad_id' => $inputs['prioridad'],
                'fecha_vencimiento' => $fechaVencimiento,
                'usuario_asignado' => $inputs['usuarioAsignado'],
            ];
        }

        // Inserción masiva
        Tarea::insert($nuevasTareas);

        return response()->json([
            'message' => 'Tareas creadas exitosamente',
            'cantidad' => count($nuevasTareas)
        ]);
    }
}
