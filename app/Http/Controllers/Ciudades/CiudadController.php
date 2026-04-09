<?php

namespace App\Http\Controllers\Ciudades;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;
use Illuminate\Http\Request;

class CiudadController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $ciudades = Ciudad::whereHas('departamento.pais', function ($q) {
            $q->where('codigo', 'CO');
        })
            ->with('departamento:id,nombre')
            ->when($search, function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            })
            ->paginate($perPage);

        $ciudades->getCollection()->transform(function ($ciudad) {
            return [
                'value' => $ciudad->id,
                'label' => "{$ciudad->departamento->nombre}-{$ciudad->nombre}",
            ];
        });

        return response()->json([
            'ciudades' => $ciudades
        ]);
    }
}
