<?php

namespace App\Http\Controllers\TipoPago;

use App\Http\Controllers\Controller;
use App\Models\TipoPago;

class TipoPagoController extends Controller
{
    public function listTiposPago() {
        $tiposPago = TipoPago::all();

        return response()->json([
            'tiposPago' => $tiposPago
        ]);
    }
}
