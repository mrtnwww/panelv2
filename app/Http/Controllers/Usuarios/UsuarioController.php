<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Subtipousuario;
use App\Models\Usuario;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function listMyUsers(Request $request)
    {
        $usuario = auth()->user();
        $empresaId = $usuario->empresa_id;

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

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
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('persona.nombre', 'like', "%{$search}%")
                        ->orWhere('usuario.correo', 'like', "%{$search}%");
                });
            })
            ->orderBy('usuario.id', 'desc')
            ->paginate($perPage);

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

    public function saveUsuario(Request $request)
    {
        return $this->procesarPersistencia($request);
    }

    public function updateUsuario(Request $request)
    {
        return $this->procesarPersistencia($request);
    }

    private function procesarPersistencia(Request $request)
    {
        $usuarioId = $request->input('id');
        $isUpdate = !empty($usuarioId);

        $request->validate([
            'email' => 'required|email|unique:usuario,correo' . ($isUpdate ? ",$usuarioId" : ""),
            'nombre' => 'required|string',
            'roles' => 'required'
        ], [
            'email.unique' => 'El correo ya se encuentra registrado.'
        ]);

        $usuario = Usuario::findOrNew($usuarioId);
        // Crear o actualizar persona
        $persona = $usuario->persona_id ? Persona::find($usuario->persona_id) : new Persona;

        $persona->nombre = $request['nombre'];
        $persona->ciudad_id = auth()->user()->persona->ciudad->id;
        $persona->save();

        // Guardar usuario
        $usuario->correo = $request['email'];

        if ($request->filled('password')) {
            $usuario->password = \Hash::make($request['password']);
        }

        $usuario->subtipousuario_id = $usuario->subtipousuario_id ?? 0;
        $usuario->fecha_vence = $request['fechaCaducidad'] ?? null;
        $usuario->persona_id = $persona->id;

        $usuario->empresa_id = $request->filled('empresa')
            ? $request['empresa']
            : auth()->user()->empresa_id;

        $usuario->save();

        // Guardar roles
        UsuarioTipoUsuario::where('id_usuario', $usuario->id)->delete();

        $roles = is_array($request['roles']) ? $request['roles'] : json_decode($request['roles']);

        foreach ($roles as $item) {
            UsuarioTipoUsuario::create([
                'id_usuario' => $usuario->id,
                'id_tipo_usuario' => $item
            ]);
        }

        return response()->json([
            'message' => $isUpdate ? 'Usuario actualizado' : 'Usuario creado'
        ], $isUpdate ? 201 : 200);
    }

    public function deleteUsuario(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:usuario,id'
        ]);

        DB::transaction(function () use ($request) {
            $usuario = Usuario::findOrFail($request['id']);

            $personaId = $usuario->persona_id;

            // Se borran los registros de la tabla intermedia
            UsuarioTipoUsuario::where('id_usuario', $usuario->id)->delete();

            $usuario->delete();

            // Borrar la persona asociada
            if ($personaId) Persona::where('id', $personaId)->delete();
        });

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ], 200);
    }
}
