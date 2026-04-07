<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteAdministrativoExportExcel implements FromView, ShouldAutoSize, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public $creditos = [];
    public $empresa = '';
    public $total = [];

    public function __construct($creditos, $total, $empresa)
    {
        $this->creditos = $creditos;
        $this->empresa = $empresa;
        $this->total = $total;
    }

    public function view(): View
    {
        return view('excel.informeAdministrativo', [
            'creditos' => $this->creditos,
            'empresa' => $this->empresa,
            'total' => $this->total,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('C:J')->getNumberFormat()->setFormatCode('[$$-C0A] #,##0');
    }
}
