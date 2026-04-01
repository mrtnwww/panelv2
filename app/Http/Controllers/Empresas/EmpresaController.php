<?php

namespace App\Http\Controllers\Empresas;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function listMyCompanys(Request $request)
        {
            $empresaId = auth()->user()->empresa_id;

            $search = $request->input('search');
            $perPage = $request->input('perPage', 10);

            $empresas = Empresa::query()
                ->select(['id', 'razon_social'])
                ->where(function ($query) use ($empresaId) {
                    $query->where('id', $empresaId)
                        ->orWhere('aliado', $empresaId)
                        ->orWhere('sede', $empresaId);
                })
                ->when($search, function ($query, $search) {
                    $query->where('razon_social', 'like', "%{$search}%");
                })
                ->paginate($perPage);

            return response()->json(compact('empresas'));
        }
}
