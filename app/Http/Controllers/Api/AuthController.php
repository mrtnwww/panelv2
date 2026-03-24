<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = Usuario::where('correo', $request->email)
            ->where('subtipousuario_id', '!=', 7)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no registrado'
            ], 401);
        }

        // Validaciones personalizadas
        if ($user->dias_vigencia && $user->ult_acceso) {
            $dias = now()->diffInDays($user->ult_acceso);

            if ($dias > $user->dias_vigencia) {
                $user->bloqueado = 1;
                $user->save();

                return response()->json([
                    'message' => 'Usuario bloqueado por inactividad'
                ], 403);
            }
        }

        if ($user->fecha_vence && $user->fecha_vence < now()) {
            return response()->json([
                'message' => 'Usuario vencido'
            ], 403);
        }

        // Login
        if (!Auth::attempt(['correo' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $request->session()->regenerate();

        // actualizar último acceso
        $user->ult_acceso = now();
        $user->save();

        $user->load([
            'persona:id,nombre',
            'empresa:id,razon_social'
        ]);

        return response()->json([
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout correcto']);
    }

    public function user(Request $request)
    {
        return $request->user();
    }
}
