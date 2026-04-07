<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CorresponsalExport implements FromView, ShouldAutoSize
{
    public $datos = [];
    public $estadoFuncion = false;

    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($datos, $estadoFuncion)
    {
        $this->datos = $datos;
        $this->estadoFuncion = $estadoFuncion;
    }

    public function view(): View
    {
        return view('excel.informeCorresponsal', [
            'datos' => $this->datos,
            'estadoFuncion' => $this->estadoFuncion
        ]);
    }
}
