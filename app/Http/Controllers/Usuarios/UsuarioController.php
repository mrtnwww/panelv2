<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Usuario;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function listMyUsers()
    {
        try {
            $usuario = auth()->user();

            $empresaId = $usuario->empresa_id;

            $userList = Usuario::join('persona', 'persona.id', '=', 'usuario.persona_id')
                ->where('usuario.empresa_id', $empresaId)
                ->where('usuario.subtipousuario_id', '!=', 7)
                ->select(
                    'persona.nombre',
                    'usuario.correo',
                    'usuario.id as idUsuario',
                    'usuario.image',
                    'usuario.fecha_vence',
                    'usuario.bloqueado',
                    'usuario.created_at',
                    'usuario.ult_acceso'
                )
                ->get();

            $tiposUsuarios = UsuarioTipoUsuario::join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
                ->select('usuario_tipo_usuario.id_usuario', 'subtipousuario.nombre')
                ->get()
                ->groupBy('id_usuario');

            $resultado = $userList->map(function ($item) use ($tiposUsuarios, $empresaId) {

                $tipos = $tiposUsuarios[$item->idUsuario] ?? collect();

                $image = 'https://www.slotcharter.net/wp-content/uploads/2020/02/no-avatar.png';
                if ($item->image) {
                    $expiracion = Carbon::now()->addMinutes(30); // Establecer la expiración en 5 minutos
                    // $image = Storage::disk('s3')->temporaryUrl($item->image, $expiracion);
                }

                return [
                    'image' => $image,
                    'nombre' => $item->nombre,
                    'correo' => $item->correo,
                    'empresa_id' => $empresaId,
                    'idUsuario' => $item->idUsuario,
                    'tipo' => $tipos->pluck('nombre')->values(),
                    'fecha_creacion' => $item->created_at
                        ? Carbon::parse($item->created_at)->format('d/m/Y')
                        : null,
                    'ult_inicio_sesion' => $item->ult_acceso
                        ? Carbon::parse($item->ult_acceso)->format('d/m/Y H:i:s')
                        : null
                ];
            });

            return response()->json([
                'clientes' => $resultado
            ]);
        } catch (\Exception $ex) {
            return response()->json(['status' => $ex->getCode(), 'message' => $ex->getMessage()], 422);
        }
    }
}
