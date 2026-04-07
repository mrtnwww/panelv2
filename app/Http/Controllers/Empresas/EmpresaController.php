<?php

namespace App\Http\Controllers\Empresas;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\RegistroAliado;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function listMyCompanys(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $tab = $request->input('tab');
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);

        if (!empty($tab) && in_array($tab, ['alianzas_firmadas', 'alianzas_pendientes'])) {
            $empresas = RegistroAliado::where('id_empresa_principal', $empresaId)
                ->when($tab === 'alianzas_firmadas', fn($q) => $q->whereNotNull('firmado'))
                ->when($tab === 'alianzas_pendientes', fn($q) => $q->whereNull('firmado'))
                ->when(
                    $search,
                    fn($q, $search) =>
                    $q->where('nombre', 'like', "%{$search}%")
                )
                ->orderBy('nombre')
                ->paginate($perPage);
        } else {
            $empresas = Empresa::query()
                ->select(['id', 'razon_social', 'correo', 'aliado', 'sede', 'periodicidad_empresa'])
                ->when(empty($tab), function ($query) use ($empresaId) {
                    $query->where(function ($q) use ($empresaId) {
                        $q->where('id', $empresaId)
                            ->orWhere('sede', $empresaId)
                            ->orWhere('aliado', $empresaId);
                    });
                })
                ->when($tab === 'aliados', fn($q) => $q->where('aliado', $empresaId))
                ->when($tab === 'sedes', fn($q) => $q->where('sede', $empresaId))
                ->when(
                    $search,
                    fn($q, $search) =>
                    $q->where('razon_social', 'like', "%{$search}%")
                )
                ->orderBy('razon_social')
                ->paginate($perPage);
        }

        return response()->json([
            'empresas' => $empresas
        ]);
    }
}
