<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacturasExport implements FromView, ShouldAutoSize
{
    public $datos = [];
    public $totalAbonado = 0;

    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($datos, $totalAbonado)
    {
        $this->datos = $datos;
        $this->totalAbonado = $totalAbonado;
    }

    public function view(): View
    {
        return view('excel.informeFacturas', [
            'datos' => $this->datos,
            'totalAbonado' => $this->totalAbonado
        ]);
    }
}
