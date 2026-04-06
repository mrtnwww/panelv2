<?php

namespace App\Http\Controllers\Cajeras;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Http\Request;

class CajeraController extends Controller
{
    public function listCajerasAbono(Request $request)
    {
        $usuario = auth()->user();

        $empresaId = $usuario->empresa_id;

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $listaSedesAliados = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id')
            ->push($empresaId);

        $usuarios = Usuario::whereIn('empresa_id', $listaSedesAliados)
            ->where('subtipousuario_id', '!=', 7)
            ->withTrashed()
            ->pluck('id');

        $cajeras = Abono::select('persona.id', 'persona.nombre')
            ->join('usuario', 'abono.user_id', '=', 'usuario.id')
            ->join('persona', 'usuario.persona_id', '=', 'persona.id')
            ->where('persona.nombre', 'like', '%' . $search . '%')
            ->whereIn('abono.user_id', $usuarios)
            ->orderBy('persona.nombre')
            ->distinct()
            ->paginate($perPage);

        return response()->json([
            'cajeras' => $cajeras
        ]);
    }
}
