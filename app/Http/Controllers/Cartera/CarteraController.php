<?php

namespace App\Http\Controllers\Cartera;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Creditos\CreditoController;
use App\Models\Condonacion;
use App\Models\Credito;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarteraController extends Controller
{
    public function listCartera(Request $request) {
        $empresaId = auth()->user()->empresa_id;

        $aliado = $request->input('conditions.idAliado', null);
        $conditions = $request->input('conditions', []);

        $listaAliados = is_null($aliado)
            ? Empresa::where('aliado', $empresaId)
                ->orWhere('sede', $empresaId)
                ->pluck('id')
                ->prepend($empresaId)
                ->unique()
                ->toArray()
            : [$aliado];

        $creditosIds = Credito::whereIn('empresa_id', $listaAliados)
            ->whereHas('proyeccionesCartera')
            ->pluck('id')
            ->toArray();

        $creditos = Credito::select([
                'credito.*',
                DB::raw('COALESCE(SUM(CASE WHEN abono.deleted_at IS NULL THEN abono.valor ELSE 0 END), 0) as total_abonos')
            ])
            ->leftJoin('abono', 'credito.id', '=', 'abono.credito_id')
            // ->whereIn('credito.empresa_id', $listaAliados)
            ->whereIn('credito.id', $creditosIds)
            ->groupBy('credito.id')
            ->with([
                'proyecciones', // proyecciones del credito sin filtro
                'proyeccionesCartera' => function ($query) {
                    $query->select([
                            'credito_id',
                            DB::raw('SUM(COALESCE(valor_cuota, 0)) as total_valor_cuota'),
                            DB::raw('COUNT(credito_id) as conteo'),
                            DB::raw('MAX(diasMora) as diasMora')
                        ])
                        ->groupBy('credito_id');
                }
            ])
            ->applyConditions($conditions)
            ->get();

        $moras = [ 'mora_1_10' => 0, 'mora_11_30' => 0, 'mora_31_60' => 0, 'mora_61_90' => 0, 'mora_91_120' => 0, 'mora_120_mas' => 0 ];

        foreach ($creditos as $credito) {
            if (count($credito->proyeccionesCartera) == 0) continue;

            $totalAbonado = $credito->total_abonos ?? 0;

            // Validar si se han realizado condonaciones al credito
            $valorCondonaciones = Condonacion::whereIn('abono_id', function ($query) use($credito) {
                $query->select('id')->from('abono')->where('credito_id', $credito->id);
            })->where('concepto_condonacion', 'credito')->sum('valor_condonado');

            $totalAbonado += ($valorCondonaciones ?? 0);

            $sobra = app(CreditoController::class)->calculoValorAFavor($credito->proyecciones[0]->valor_cuota, $totalAbonado, $credito->proyecciones, $credito) ?? 0;

            foreach ($credito->proyeccionesCartera as $proyeccion) {
                $diasMora = $proyeccion->diasMora;
                $monto = $proyeccion->total_valor_cuota
                    ? $proyeccion->total_valor_cuota - $sobra
                    : $credito->val_cuotas * $proyeccion->conteo - $sobra;

                // Se adiciona al valor pendiente los gastos de cobranza y los intereses moratorios
                $adicionales = $credito->proyecciones
                    ->sum(fn($proyeccion) => $proyeccion->pagado == 0
                        ? round(($proyeccion->gastos_cobranza ?? 0)) + round(($proyeccion->intereses_moratorios ?? 0))
                        : 0
                    );
                $monto += $adicionales;

                if ($diasMora >= 1 && $diasMora <= 10) {
                    $moras['mora_1_10'] += $monto;
                } elseif ($diasMora >= 11 && $diasMora <= 30) {
                    $moras['mora_11_30'] += $monto;
                } elseif ($diasMora >= 31 && $diasMora <= 60) {
                    $moras['mora_31_60'] += $monto;
                } elseif ($diasMora >= 61 && $diasMora <= 90) {
                    $moras['mora_61_90'] += $monto;
                } elseif ($diasMora >= 91 && $diasMora <= 120) {
                    $moras['mora_91_120'] += $monto;
                } else {
                    $moras['mora_120_mas'] += $monto;
                }
            }
        }

        $totalCartera = array_sum($moras);

        $resumenCartera = $moras;
        $resumenCartera['total'] = $totalCartera;

        return response()->json([
            'resumenCartera' => $resumenCartera
        ]);
    }
}
