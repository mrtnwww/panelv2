<?php

namespace App\Http\Controllers\Abonos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\MobileController;
use App\Models\Abono;
use Illuminate\Http\Request;

class AbonoController extends Controller
{
    function procesarAbonos($abono, $calculoAbono = null) {
        // $planPagos = (new MobileController)->obtenerPlanDePagos($abono->credito_id);
        $planPagos = app(MobileController::class)->obtenerPlanDePagos($abono->credito_id, $calculoAbono);
        array_shift($planPagos); // Se elimina el primer elemento cuyos valores se encuentran en 0
        // Se consultan los abonos realizados desde el mas antiguo al mas reciente
        $abonosCredito = Abono::where('credito_id', $abono->credito_id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'valor']);

        // Se itera el plan de pagos y los abonos realizados para determinar los detalles de cada abono
        $indicePlan = 0;
        $abonosAsociados = [];

        foreach ($abonosCredito as $a) {
            $valorPendiente = $a->valor; // Valor del abono actual (Se itera tanto el abono actual como los abonos anteriores)
            $detalleAbono = [ 'intereses' => 0, 'firmaElec' => 0, 'aval' => 0, 'avalIva' => 0, 'capital' => 0, 'otros' => 0 ];

            while ($indicePlan < count($planPagos)) {
                $p = &$planPagos[$indicePlan];

                if ($valorPendiente <= 0) break;

                // Se cubren los intereses pendientes
                if ($p['intereses'] > 0) {
                    $aCubrir = min($valorPendiente, $p['intereses']);
                    $detalleAbono['intereses'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['intereses'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre la firma electronica pendiente
                if ($valorPendiente > 0 && $p['firmaElec'] > 0) {
                    $aCubrir = min($valorPendiente, $p['firmaElec']);
                    $detalleAbono['firmaElec'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['firmaElec'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el aval pendiente
                if ($valorPendiente > 0 && $p['aval'] > 0) {
                    $aCubrir = min($valorPendiente, $p['aval']);
                    $detalleAbono['aval'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['aval'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el iva del aval pendiente
                if ($valorPendiente > 0 && $p['avalIva'] > 0) {
                    $aCubrir = min($valorPendiente, $p['avalIva']);
                    $detalleAbono['avalIva'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['avalIva'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el valor de otros gastos pendientes
                if ($valorPendiente > 0 && $p['otros'] > 0) {
                    $aCubrir = min($valorPendiente, $p['otros']);
                    $detalleAbono['otros'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['otros'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el capital pendiente
                if ($valorPendiente > 0 && $p['capital'] > 0) {
                    $aCubrir = min($valorPendiente, $p['capital']);
                    $detalleAbono['capital'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['capital'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Si el abono cubrio la cuota se avanza a la siguiente
                if ($p['intereses'] <= 0 && $p['firmaElec'] <= 0 && $p['capital'] <= 0) {
                    $indicePlan++;
                } else {
                    break;
                }
            }

            $abonosAsociados[] = [
                'valorAbono' => $a->valor,
                'detalles' => $detalleAbono,
            ];

            if ($a->id == $abono->id) break;
        }

        return $abonosAsociados;
    }
}
