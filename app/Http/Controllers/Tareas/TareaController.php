<?php

namespace App\Http\Controllers\Tareas;

use App\Http\Controllers\Controller;
use App\Models\Tarea;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TareaController extends Controller
{
    function listTasks(Request $request) {
        $usuario = $request->user();

        $currentUserId  = $usuario->id;
        $empresaId      = $usuario->empresa_id;

        $hoy = Carbon::today();

        $perPage = $request->input('per_page', 10); // registros por pagina
        $searchTerm = $request->input('search', '');
        $conditions = [
            'filtroEstado' => $request->input('tab', '')
        ];

        // verificar roles
        $roles = UsuarioTipoUsuario::where('id_usuario', $currentUserId)
            ->whereIn('id_tipo_usuario', [2, 6])
            ->pluck('id_tipo_usuario');

        $isAdmin    = $roles->contains(2);
        $isCobranza = $roles->contains(6);

        $hasTasks  = $isCobranza ?
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

        if (!$isAdmin) $baseQuery->where('tareas.usuario_asignado', $currentUserId);

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
}
