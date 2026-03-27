<?php

namespace App\Http\Controllers\ReporteCentralesTipo;

use App\Http\Controllers\Controller;
use App\Models\ReporteCentralesTipo;

class ReporteCentralesTipoController extends Controller
{
    public function index() {
        return response()->json([
            'reportes' => ReporteCentralesTipo::all()
        ]);
    }
}
