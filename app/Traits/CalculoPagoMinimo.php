<?php

namespace App\Traits;

trait CalculoPagoMinimo
{
    public function pagoMinimo($proyecciones, $modular, $valorCuota, $soloMora = false, $estadoFunciones = false, $gCobranza = 0, $iMoratorios = 0) {
        $vInteresesMoratorios = 0;
        $vGastosCobranza = 0;
        $cuotaMinPago = 0;
        $enMora = false;

        foreach ($proyecciones as &$p) {
            if ($p->pagado == 0) {
                $vInteresesMoratorios += round($p->intereses_moratorios ?? 0);
                $vGastosCobranza += round($p->gastos_cobranza ?? 0);

                if ($p['valor_mora'] > 0) {
                    $enMora = true;
                }
            }

            if ($p['valor_mora'] > 0) {
                $p['cuotaMinPago'] = round(
                    ($p['valor_mora'] ?? 0) -
                    ($p['gastos_cobranza'] ?? 0) -
                    ($p['intereses_moratorios'] ?? 0)
                );
            } else {
                if ($soloMora == false) {
                    $creditoCuota = $p['valor_cuota'] ? $p['valor_cuota'] : $valorCuota;
                    if ($creditoCuota > $modular) {
                        $p['cuotaMinPago'] = $creditoCuota - $modular;
                    } else {
                        $p['cuotaMinPago'] = $creditoCuota;
                    }
                } else {
                    $p['cuotaMinPago'] = 0;
                }
            }
        }


        if ($enMora) {
            $infoCuota = collect($proyecciones)
                ->filter(fn($p) => $p->pagado == 0 && $p->valor_mora > 0)
                ->sum('cuotaMinPago');

            // Al valor minimo a pagar se le incluyen gastos de cobranza e intereses moratorios
            if ($estadoFunciones) {
                if ($iMoratorios == 1 && $gCobranza == 1) {
                    $cuotaMinPago = $infoCuota + $vInteresesMoratorios + $vGastosCobranza;
                } else if ($iMoratorios == 1) {
                    $cuotaMinPago = $infoCuota + $vInteresesMoratorios;
                } else if ($gCobranza == 1) {
                    $cuotaMinPago = $infoCuota + $vGastosCobranza;
                } else {
                    $cuotaMinPago = $infoCuota;
                }
            } else {
                $cuotaMinPago = $infoCuota + $vInteresesMoratorios + $vGastosCobranza;
            }
        } else {
            $infoCuota = collect($proyecciones)->where('pagado', 0)->first();
            if ($infoCuota) $cuotaMinPago = $infoCuota['cuotaMinPago'];
        }

        return $cuotaMinPago;
    }
}
