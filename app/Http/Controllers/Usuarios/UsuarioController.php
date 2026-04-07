<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Subtipousuario;
use App\Models\Usuario;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function listMyUsers(Request $request)
    {
        $usuario = auth()->user();
        $empresaId = $usuario->empresa_id;

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $query = Usuario::join('persona', 'persona.id', '=', 'usuario.persona_id')
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
            );

        if (!empty($search)) $query->where('persona.nombre', 'LIKE', '%' . $search . '%');

        $userList = $query->paginate($perPage);

        $tiposUsuarios = UsuarioTipoUsuario::join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
            ->select('usuario_tipo_usuario.id_usuario', 'subtipousuario.nombre')
            ->get()
            ->groupBy('id_usuario');

        $tiposUsuarios = UsuarioTipoUsuario::join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
            ->select('usuario_tipo_usuario.id_usuario', 'subtipousuario.nombre', 'subtipousuario.id')
            ->get()
            ->groupBy('id_usuario');

        // Subtipos usuarios válidos
        $subtipousuarios = Subtipousuario::whereNotIn('id', [1, 7])->get();

        $userList->getCollection()->transform(function ($item) use ($tiposUsuarios, $empresaId) {

            $tipos = $tiposUsuarios[$item->idUsuario] ?? collect();

            $image = 'https://www.slotcharter.net/wp-content/uploads/2020/02/no-avatar.png';

            $tipo = $tipos->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre
                ];
            })->values();

            return [
                'tipo' => $tipo,
                'image' => $image,
                'nombre' => $item->nombre,
                'correo' => $item->correo,
                'empresa_id' => $empresaId,
                'idUsuario' => $item->idUsuario,
                'fecha_creacion' => $item->created_at
                    ? Carbon::parse($item->created_at)->format('d/m/Y')
                    : null,
                'ult_inicio_sesion' => $item->ult_acceso
                    ? Carbon::parse($item->ult_acceso)->format('d/m/Y H:i:s')
                    : null,
                'fecha_vence' => $item->fecha_vence
                    ? Carbon::parse($item->fecha_vence)->format('d/m/Y')
                    : null
            ];
        });

        return response()->json([
            'usuarios' => $userList,
            'roles' => $subtipousuarios
        ]);
    }
}
