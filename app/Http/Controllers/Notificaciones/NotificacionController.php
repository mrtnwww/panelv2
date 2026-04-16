<?php

namespace App\Http\Controllers\Notificaciones;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Notification;
use App\Models\NotificationHasUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    public function getNotificaciones()
    {
        $user = auth()->user();

        // IDs de empresas aliadas/sedes
        $empresasIds = Empresa::where('aliado', $user->empresa_id)
            ->orWhere('sede', $user->empresa_id)
            ->pluck('id');

        $notifications = NotificationHasUser::query()
            ->select(
                'notification.title',
                'notification.content',
                'notification.url',
                'notification.created_at',
                DB::raw('MIN(notification_has_user.visualized_at) as visualized_at'),
                DB::raw('MIN(notification_has_user.id) as id')
            )
            ->join('notification', 'notification.id', '=', 'notification_has_user.notification_id')
            ->where(function ($q) use ($user, $empresasIds) {
                $q->where('notification_has_user.user_id', $user->id)
                    ->orWhereIn('notification.empresa_id', $empresasIds);
            })
            ->groupBy(
                'notification.id',
                'notification.title',
                'notification.content',
                'notification.url',
                'notification.created_at'
            )
            ->orderByDesc('notification.created_at')
            ->limit(100)
            ->get()
            ->each(function ($n) {
                $n->created_at = Carbon::parse($n->created_at)
                    ->subHours(5)
                    ->format('Y-m-d H:i:s');
            });

        return response()->json([
            'currentUser' => $user->id,
            'notifications' => $notifications
        ]);
    }

    public function visualizeNotificaciones()
    {
        $empresaId = auth()->user()->empresa_id;
        $usuarioId = auth()->user()->id;
        $hoy = Carbon::now();

        // Actualizar las notificaciones del usuario que no han sido visualizadas
        NotificationHasUser::where('user_id', $usuarioId)
            ->whereNull('visualized_at')
            ->update(['visualized_at' => $hoy]);

        // Marcar las notificaciones padres que aún no han sido visualizadas
        Notification::where('user_id', $usuarioId)
            ->whereNull('visualized_by')
            ->update(['visualized_by' => $usuarioId]);

        // Obtener IDs de empresas aliadas y sedes en un solo array
        $listaSedesAliadosIds = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id')
            ->toArray();

        if (!empty($listaSedesAliadosIds)) {
            // Actualizar las notificaciones de empresas aliadas/sedes que no han sido visualizadas
            Notification::whereIn('empresa_id', $listaSedesAliadosIds)
                ->whereNull('visualized_by')
                ->update(['visualized_by' => $usuarioId]);

            // Obtener IDs de notificaciones de empresas aliadas/sedes
            $notificationIds = Notification::whereIn('empresa_id', $listaSedesAliadosIds)
                ->pluck('id')
                ->toArray();

            if (!empty($notificationIds)) {
                // Marcar como visualizadas todas las notificaciones relacionadas
                NotificationHasUser::whereIn('notification_id', $notificationIds)
                    ->whereNull('visualized_at')
                    ->update(['visualized_at' => $hoy]);
            }
        }

        return response()->json([
            'message' => 'Notificaciones leídas correctamente'
        ]);
    }
}
