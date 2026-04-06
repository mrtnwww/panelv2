<?php

namespace App\Http\Controllers\Destinos;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\LineasCredito;
use App\Models\ParametrosEstadoFunciones;
use Illuminate\Http\Request;

class DestinoController extends Controller
{
    public function listDestinos() {
        $empresaId = auth()->user()->empresa_id;

        // lineas de credito
        $empresa = Empresa::find($empresaId);
        $vEmpresaId = $empresaId;
        // Validar si la empresa es un aliado o una sede
        if ($empresa->aliado || $empresa->sede) {
            $empresaPrincipal = Empresa::where('id', $empresa->aliado ?? $empresa->sede)->first();
            if ($empresaPrincipal) $vEmpresaId = $empresaPrincipal->id;
        }

        $creditoOrdinario = ParametrosEstadoFunciones::where('empresa_id', $vEmpresaId)
            ->whereHas('estado_funcion', function($query) {
                $query->where('nombre_funcion', 'Ocultar línea de crédito ordinario');
            })
            ->exists();

        // Obtener las líneas de crédito
        $lineasCredito = LineasCredito::where('empresa_id', $vEmpresaId)
            ->orWhereNull('empresa_id')
            ->orderBy('id')
            ->get();

        if ($lineasCredito->count() > 1 && $creditoOrdinario) {
            $lineasCredito = $lineasCredito->filter(fn($p) => $p->id != 1)->values();
        }

        return response()->json([
            'lineasCredito' => $lineasCredito
        ]);
    }
}
