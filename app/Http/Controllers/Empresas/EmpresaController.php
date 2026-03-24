<?php

namespace App\Http\Controllers\Empresas;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    function listMyCompanys()
    {
        $user = auth()->user();

        $empresaId = $user->empresa_id;

        // Obtener todas las empresas relevantes en una sola consulta
        $empresas = Empresa::select('id', 'razon_social')
            ->where(function($query) use ($empresaId) {
                $query->where('id', $empresaId)
                    ->orWhere('aliado', $empresaId)
                    ->orWhere('sede', $empresaId);
            })->get();

        // Retornar la respuesta exitosa con las empresas
        return response()->json([
            'empresas' => $empresas
        ]);
    }
}
