<?php

namespace App\Traits;

trait CalculoPagoMinimoTemp
{
    public function pagoMinimoTemp($proyecciones, $modular, $valorCuota, $soloMora = false, $estadoFunciones = false, $gCobranza = 0, $iMoratorios = 0, $masivo = false) {
        $vInteresesMoratorios = 0;
        $vGastosCobranza = 0;
        $cuotaMinPago = 0;
        $enMora = false;

        // sufijo utilizado para actualizar en base de datos
        $suf = $masivo ? '_masivo' : '_temp';

        $proyIntereses = 'intereses_moratorios' . $suf;
        $proyGastosCob = 'gastos_cobranza' . $suf;
        $proyValorMora = 'valor_mora' . $suf;

        foreach ($proyecciones as &$p) {
            if ($p->pagado == 0) {
                $vInteresesMoratorios += round($p->$proyIntereses ?? 0);
                $vGastosCobranza += round($p->$proyGastosCob ?? 0);

                if ($p[$proyValorMora] > 0) {
                    $enMora = true;
                }
            }

            if ($p[$proyValorMora] > 0) {
                $p['cuotaMinPago'] = round(
                    ($p[$proyValorMora] ?? 0) -
                    ($p[$proyGastosCob] ?? 0) -
                    ($p[$proyIntereses] ?? 0)
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
                ->filter(fn($p) => $p->pagado == 0 && $p->$proyValorMora > 0)
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
