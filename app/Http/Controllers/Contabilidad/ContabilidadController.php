<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Credito;
use App\Models\ReciboCajaCXC;
use Illuminate\Http\Request;

class ContabilidadController extends Controller
{
    function listRecibosCXC(Request $request) {
        $usuario = auth()->user();

        $empresaId = $usuario->empresa_id;

        $conditions = $request->input('conditions', []);
        $per_page = $request->input('per_page', 10);
        $search = $request->input('search');

        $recibosQuery = ReciboCajaCXC::where('empresa_principal_id', $empresaId)
            ->with(['empresa', 'producto'])
            ->applySearch($search)
            ->applyConditions($conditions)
            ->orderBy('created_at', 'desc');

        $creditosId = $recibosQuery->cursor()
            ->flatMap(fn($item) => json_decode($item->id_creditos, true))
            ->toArray();

        $totalCXC = empty($creditosId) ? 0 : Credito::whereIn('id', $creditosId)->sum('valor_cxc');

        $recibosCaja = $recibosQuery->paginate($per_page);

        $recibosCaja->getCollection()->transform(function ($item) {
            $item->fecha = date('Y-m-d', strtotime($item->created_at));
            $item->valor_cxc = Credito::whereIn('id', json_decode($item->id_creditos))->sum('valor_cxc');
            return $item;
        });

        return response()->json([
            'id_empresa'  => $empresaId,
            'recibosCaja' => $recibosCaja,
            'totalCXC' => $totalCXC
        ]);
    }
}
