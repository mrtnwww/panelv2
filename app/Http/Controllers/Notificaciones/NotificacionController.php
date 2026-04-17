<?php

namespace App\Http\Controllers\Notificaciones;

use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Notification;
use App\Models\NotificationHasUser;
use App\Models\Usuario;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    public function newNotification($basicOpt, $optionsUser)
    {

        /**
         * Validar si la notificación existe, siempre y cuando se cuente con
         * el "client_id", a partir de allí, validará si ya existe una
         * notificación con el tipo recibido como parámetro
         */
        $notificationExists = false;
        if (isset($basicOpt['client_id'])) {

            $notificationExists = Notification::where('client_id', $basicOpt['client_id'])
                ->where('type', $basicOpt['type'])
                ->exists();
        }

        if (!$notificationExists) {

            try {
                // desmarcar al cliente como pendiente de validar al realizar nueva consulta en centrales
                if ($basicOpt['type'] == 'CLIENT_ANALIZED') {
                    Cliente::where('id', $basicOpt['client_id'])
                        ->update(['nueva_consulta_centrales' => 0]);

                    // envio notificacion credito aprobado
                    $cliente = Cliente::find($basicOpt['client_id']);
                    if ($cliente)
                        (new ClienteController)->envioComunicacionConsultaAprobada($cliente);
                }

                /**
                 * Crear el nuevo registro en la tabla "notification",
                 * el parámetro "basicOpt" debe ser un array y debe
                 * contener los siguientes valores:
                 *
                 * - empresa_id => int
                 * - user_id    => int (opcional)
                 * - title      => string
                 * - content    => string
                 * - url        => string (opcional)
                 */
                $notification = Notification::create($basicOpt);

                $usersId = $this->getUsersNewNotif($optionsUser, $basicOpt['empresa_id']);
            } catch (\Exception $ex) {
                return response()->json(['status' => $ex->getCode(), 'message' => $ex->getMessage()], 422);
            }


            /**
             * Se recorre el array de los ids de usuario y se crea un registro
             * en la tabla "notification_has_user" por cada uno de ellos.
             */
            foreach ($usersId as $value) {

                try {

                    $notificacionHasUser = new NotificationHasUser;
                    $notificacionHasUser->notification_id = $notification->id;
                    $notificacionHasUser->user_id = $value;
                    $notificacionHasUser->save();
                } catch (\Throwable $th) {
                    return $th;
                }
            }

            return 'success';
        }
    }

    private function getUsersNewNotif($options, $empresa_id)
    {

        /**
         * Los usuarios será de manera predeterminada, el parámetro
         * "options", en caso de no cumplirse ninguna de las posteriores
         * condiciones, significa que dicho parámetro es un array de enteros
         * que serán asumidos como ids de usuarios.
         */
        $users = $options;

        /**
         * Si el parámetro "options" es un entero, se asume que éste
         * corresponde al id de un tipo de usuario, y se procede a guardar
         * en la variable "users" los usuarios que tengan dicho tipo de
         * usuario.
         */
        if (is_int($options)) {

            $users = UsuarioTipoUsuario::select(DB::raw('DISTINCT(usuario_tipo_usuario.id_usuario)'))
                ->join('usuario', 'usuario.id', '=', 'usuario_tipo_usuario.id_usuario')
                ->where('usuario.empresa_id', $empresa_id)
                ->where('usuario_tipo_usuario.id_tipo_usuario', $options)
                ->whereNull('usuario.deleted_at')
                ->pluck('usuario_tipo_usuario.id_usuario')
                ->toArray();
        } else if (is_array($options)) {

            /**
             * Si el parámetro es un array y éste contiene las llaves validadas
             * en la condición, se guarda en la variable "users", los usuarios
             * que tengan tengan alguno de los ids de tipo de usuario establecidos
             * en la llave "whereType" del array "options".
             *
             * Posteriormente, se agregan los ids de los usuarios almacenados en
             * la llave "whereId" del mismo array.
             */
            if (isset($options['whereType']) && isset($options['whereId'])) {

                $users = UsuarioTipoUsuario::select(DB::raw('DISTINCT(usuario_tipo_usuario.id_usuario)'))
                    ->join('usuario', 'usuario.id', '=', 'usuario_tipo_usuario.id_usuario')
                    ->where('usuario.empresa_id', $empresa_id)
                    ->whereIn('usuario_tipo_usuario.id_tipo_usuario', $options['whereType'])
                    ->whereNull('usuario.deleted_at')
                    ->pluck('usuario_tipo_usuario.id_usuario')
                    ->toArray();

                $users = array_merge($users, $options['whereId']);
            }
        }

        $administrators = Usuario::select('usuario.id')
            ->where('usuario.empresa_id', $empresa_id)
            ->where('usuario_tipo_usuario.id_tipo_usuario', 2)
            ->join('usuario_tipo_usuario', 'usuario_tipo_usuario.id_usuario', '=', 'usuario.id')
            ->pluck('usuario.id')
            ->toArray();

        $users = array_merge($users, $administrators);

        return $users;
    }

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
