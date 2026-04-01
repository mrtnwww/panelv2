<?php

namespace App\Http\Controllers\Cajeras;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Http\Request;

class CajeraController extends Controller
{
    public function listCajerasAbono()
    {
        $usuario = auth()->user();

        $empresaId = $usuario->empresa_id;

        $listaSedesAliados = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id')
            ->push($empresaId);

        $usuarios = Usuario::whereIn('empresa_id', $listaSedesAliados)
            ->where('subtipousuario_id', '!=', 7)
            ->withTrashed()
            ->pluck('id');

        $cajeros = Abono::select('persona.id', 'persona.nombre')
            ->join('usuario', 'abono.user_id', '=', 'usuario.id')
            ->join('persona', 'usuario.persona_id', '=', 'persona.id')
            ->whereIn('abono.user_id', $usuarios)
            ->orderBy('persona.nombre')
            ->distinct()
            ->get()
            ->map(function ($usuario) {
                return [
                    'id' => $usuario->id,
                    'nombre' => strtoupper($usuario->nombre)
                ];
            });

        return response()->json([
            'cajeras' => $cajeros
        ]);
    }
}
