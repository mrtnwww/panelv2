<?php

namespace App\Http\Controllers\Ciudad;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;

class CiudadController extends Controller
{
    function index()
    {
        $ciudades = Ciudad::whereHas('departamento.pais', fn($q) => $q->where('codigo', 'CO'))
            ->with('departamento')
            ->get()
            ->map(fn($ciudad) => [
                'value'     => $ciudad->id,
                'label'     => "{$ciudad->departamento->nombre}-{$ciudad->nombre}",
            ]);

        return response()->json([
            'ciudades' => $ciudades
        ]);
    }
}
