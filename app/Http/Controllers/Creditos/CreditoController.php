<?php

namespace App\Http\Controllers\Creditos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\MobileController;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Condonacion;
use App\Models\Credito;
use App\Models\CreditoProyeccion;
use App\Models\Empresa;
use App\Models\EstadoFunciones;
use App\Models\ParametrosEstadoFunciones;
use App\Models\Persona;
use App\Models\Usuario;
use App\Traits\CalculoCobranza;
use App\Traits\CalculoPagoMinimo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CreditoController extends Controller
{
    use CalculoCobranza;
    use CalculoPagoMinimo;

    public function creditDetails(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        $credito_id     = $request['id'];
        $creditoActual  = Credito::where('id', $credito_id)->first();

        // actualizar valor_cuota en la proyeccion del credito si contiene valores nulos
        $this->actualizarValorCuota($creditoActual);

        // Calculo gastos de cobranza e intereses moratorios
        $vCreditoId = array();
        $vCreditoId[] = $credito_id;
        if (!$creditoActual->fecha_gcobranza || Carbon::parse($creditoActual->fecha_gcobranza)->isToday() == false) {
            $this->calculoCobranzaIntMora($vCreditoId);
            $creditoActual->fecha_gcobranza = Carbon::now();
            $creditoActual->save();
        }

        $abonos = Abono::where('credito_id', $credito_id)->sum('valor');

        // Se valida si se ha realizado condonaciones al credito
        $valorCondonaciones = Condonacion::whereIn('abono_id', function ($query) use($creditoActual) {
            $query->select('id')->from('abono')->where('credito_id', $creditoActual->id);
        })->where('concepto_condonacion', 'credito')->sum('valor_condonado');

        // Se añade el valor de las condonaciones al total abonado
        $abonos += ($valorCondonaciones ?? 0);

        $proyeccion = CreditoProyeccion::where('credito_id', $credito_id)->get();
        $usuario = Usuario::withTrashed()->where('id', $creditoActual->user_id)->first();

        $modular = $this->calculoValorAFavor($proyeccion[0]->valor_cuota, $abonos, $proyeccion, $creditoActual) ?? 0;

        $persona = Persona::where('id', $usuario->persona_id)->first();
        $cliente = Cliente::where('id', $creditoActual->client_id)->first();

        // formatear fechas de acuerdo de pago
        $cliente->fecha_inicio_acuerdo_pago = $cliente->fecha_inicio_acuerdo_pago ? Carbon::parse($cliente->fecha_inicio_acuerdo_pago)->format('d/m/Y') : null;
        $cliente->fecha_fin_acuerdo_pago = $cliente->fecha_fin_acuerdo_pago ? Carbon::parse($cliente->fecha_fin_acuerdo_pago)->format('d/m/Y') : null;

        $cuotas = $proyeccion->where('pagado', 1)->count();

        $valorMoratorio = 0;

        $total_gastos_c = 0;
        $total_intereses_m = 0;
        foreach ($proyeccion as $p) {

            $arrayProyeccion[] = array(
                'id' => $p->id,
                'credito_id' => $p->credito_id,
                'fecha' => date("Y-m-d", strtotime($p->fecha)),
                'valor' => 0,
                'pagado' => $p->pagado,
                'enmora' => ($p->pagado == 0 && $p->diasMora > 0) ? true : false,
                'diasmora' => ($p->pagado == 0 && $p->diasMora > 0) ? $p->diasMora : 0,
                'fecha_pago' => '',
                'valor_mora' => $p->valor_mora,
                'intereses_moratorios' => round($p->intereses_moratorios),
                'gastos_cobranza' => round($p->gastos_cobranza),
                'valor_cuota' => !empty($p->valor_cuota) ? $p->valor_cuota : $creditoActual->val_cuotas,
                'pagada_capital' => $p->pagada_capital
            );

            if ($p->pagado == 0) {
                $valorMoratorio += $p->valor_mora;
            }

            if ($p->pagado == 0) {
                $total_gastos_c += round($p->gastos_cobranza);
                $total_intereses_m += round($p->intereses_moratorios);
            }
        }

        $suma = 0;
        for ($i = 0; $i < $cuotas; $i++) {
            if (isset($proyeccion[$i])) {
                if (isset($proyeccion[$i]->valor_cuota)) {
                    $suma += $proyeccion[$i]->valor_cuota;
                    $arrayProyeccion[$i]['valor'] = $proyeccion[$i]->valor_cuota;
                } else {
                    $suma += $creditoActual->val_cuotas;
                    $arrayProyeccion[$i]['valor'] = $creditoActual->val_cuotas;
                }
            }
        }

        if (isset($arrayProyeccion[$cuotas]['fecha'])) {
            $arrayProyeccion[$cuotas]['valor'] = $modular;
        }

        $condicional = [
            ['credito_id', $credito_id],
            ['pagado', 0]
        ];
        $isFinalizado =  CreditoProyeccion::where($condicional)->count();
        $diasMora =  CreditoProyeccion::where($condicional)->max('diasMora');

        $pagado = 0;
        $table = $this->CalcularCapital($creditoActual->id);

        $abonos = Abono::where('credito_id', $credito_id)->get();

        foreach ($arrayProyeccion as $key => &$item) {
            $pagado +=  $item['valor'];

            $item['capital_cuota']  = $table[$key]['capital'];
            $item['fecha_pago'] = '- -';

            foreach ($abonos as $abono) {
                $cuotasPagadas = json_decode($abono->credito_proyeccion_cuotas_pagadas, true) ?? [];

                if (in_array($item['id'], $cuotasPagadas)) {
                    $item['fecha_pago'] = Carbon::parse($abono->created_at)->format('Y-m-d');
                    $item['fecha_abono_recibo'] = $abono->created_at;
                    break;
                }
            }
        }

        $funcionMoratorioAu = EstadoFunciones::where('nombre_funcion','Intereses moratorios Automáticos')->first();
        $InteresesMoratoriosAu = ParametrosEstadoFunciones::where([['empresa_id',$empresaId],['estado_funcion_id',$funcionMoratorioAu->id]])->first();

        if($InteresesMoratoriosAu){
            $InteresesMoratoriosAu = 1;
        }else{
            $InteresesMoratoriosAu = 0;
        }

        $funcionGastoCAu = EstadoFunciones::where('nombre_funcion','Gastos de cobranza Automáticos')->first();
        $GastosCobranzaAu = ParametrosEstadoFunciones::where([['empresa_id',$empresaId],['estado_funcion_id',$funcionGastoCAu->id]])->first();

        if($GastosCobranzaAu){
            $GastosCobranzaAu = 1;
        }else{
            $GastosCobranzaAu = 0;
        }

        $valorMora = 0;
        $saldo = 0;
        /**
         * @param array $proyecciones
         * @param int  $modular
         * @param int  $valorCuota
         * @param bool $soloMora
         * @param bool $estadoFunciones
         * @param bool $gastosCobranzaaAu
         * @param bool $intMoratoriosAu
        */
        $valorMora = $this->pagoMinimo($creditoActual->proyecciones, null, null, true, true, $GastosCobranzaAu, $InteresesMoratoriosAu);

        $saldo = $creditoActual->valor_credito;
        $num = $cuotas;
        foreach ($table as $tab) {
            if($num > 0){
                $saldo -= $tab["valCuota"];
            }
            $num--;
        }
        $saldo = $saldo - $modular;

        // Si el valor de la mora es mayor a 0, se asigna el valor de la mora al saldo (pueden existir diferencias de 1 o 2 pesos)
        if (max(0, $saldo) == 0 && max(0, $valorMora) != 0) $saldo = $valorMora;

        $vAbonos = $abonos->sum('valor');
        $valorPendiente = $this->calculoLiquidacion($creditoActual, $creditoActual->proyecciones, $modular, $vAbonos);

        $empresa = Empresa::find($empresaId);

        $tabla = array(
            'id' => $credito_id,
            'InteresesMoratoriosAu' => $InteresesMoratoriosAu,
            'GastosCobranzaAu' => $GastosCobranzaAu,
            'realizado_por' => $persona->nombre,
            'created_at' => $creditoActual->created_at,
            'valor_compra' => $creditoActual->valor_compra,
            'num_cuotas' => $creditoActual->num_cuotas,
            'periocidad' => ($creditoActual->periocidad == 1) ? "Mensual" : "Quincenal",
            'val_cuotas' => $creditoActual->val_cuotas,
            'enMora' => ($diasMora > 0) ? true : false,
            'diasMora' => $diasMora,
            'ValorPendiente' => max(0, $saldo),
            'ValorMora' => max(0, $valorMora),
            'ValorPagado' => $vAbonos,
            'proyeccion' => $arrayProyeccion,
            'cliente' => $cliente,
            'finalizado' => ($isFinalizado > 0) ? 0 : 1,
            'valorMoratorio' => ($valorMoratorio < 0) ? 0 : $valorMoratorio,
            'total_gastos_c' => $total_gastos_c,
            'total_intereses_m' => $total_intereses_m,
            'fecha_credito' => Carbon::parse($creditoActual->created_at)->subHours(5)->format('Y-m-d H:i:s'),
            'valorHoy' => round($valorPendiente),
            'esAliado' => ($empresa->aliado || $empresa->sede) ? 1 : 0
        );

        return response()->json([
            'tabla' => $tabla
        ]);
    }

    private function actualizarValorCuota($creditoActual)
    {
        $proyeccion = CreditoProyeccion::where('credito_id', $creditoActual->id)->get();
        $valorCuotaCredito = $creditoActual->val_cuotas;

        $totalRegistros = $proyeccion->count();
        $totalNulos = $proyeccion->whereNull('valor_cuota')->count();

        // si todos los registros son null, no se realiza actualizacion (creditos antiguos)
        if ($totalNulos === 0 || $totalNulos === $totalRegistros) {
            return;
        }

        // se actualiza el valor de la cuota en la proyeccion
        foreach ($proyeccion as $p) {
            if (is_null($p->valor_cuota) && !empty($valorCuotaCredito)) {
                $p->valor_cuota = $valorCuotaCredito;
                $p->save();
            }
        }
    }

    public function calculoValorAFavor($valorCuota, $abonos, $proyeccion, $credito) {
        if ($abonos <= 0) {
            return 0;
        }

        $sumaPagos = 0;
        $valorAFavor = 0;

        foreach ($proyeccion as $key => $p) {
            if (!isset($valorCuota)) $p->valor_cuota = $credito->val_cuotas;
            $sumaPagos += $p->pagada_capital ? $this->CalcularCapital($credito->id)[$key]['capital'] : $p->valor_cuota;

            if (!$p->pagada_capital && $p->pagado == 0) {
                $valorAFavor = $p->valor_cuota - ($sumaPagos - $abonos);
                break;
            }
        }

        return $valorAFavor;
    }

    function CalcularCapital($id){
        $credito = Credito::find($id);
        $n = $credito->num_cuotas;
        $p = $credito->valor_compra;

        // Validacion de si el aval se debe cobrar totalizado o mes a mes
        $avalTotalizado = $credito->aval_columnas == 0 ? true : false;

        // $empresa = Empresa::where('id', $credito->empresa_id)->first();
        // $cliente = Cliente::where('id', $credito->client_id)->first();

        $hoy = \Carbon\Carbon::now();

        // Aval nominal, porcentual e iva
        $aval = 0;
        $avalPorcentaje = 0;
        if (!empty($credito->aval_porcentaje) && is_numeric($credito->aval_porcentaje)) {
            $avalPorcentaje = $credito->aval_porcentaje;
            if ($avalTotalizado) {
                $vSinAval = $p - ($p * ($credito->aval_porcentaje / 100));
                $aval = ($vSinAval * ($credito->aval_porcentaje / 100));
            } else {
                $aval = ($p * ($credito->aval_porcentaje / 100)) / $n;
            }
        } else if (!empty($credito->aval_value) && is_numeric($credito->aval_value)) {
            $aval = $avalTotalizado ? $credito->aval_value : $credito->aval_value / $n;
        }

        // IVA aval
        $avalIva = 0;
        $avalIvaPorcentaje = 0;
        if (!empty($credito->aval_iva) && is_numeric($credito->aval_iva)) {
            $avalIvaPorcentaje = $credito->aval_iva;
            $avalIva = $aval * ($credito->aval_iva / 100);
        }

        $periodicidad = $credito->periocidad;
        // acá va el tema de los vencimientos
        if ($periodicidad != 1) $n = $n * 2;
        $fechas = (new MobileController)->calculoPlanPagos(null, $periodicidad, $n);

        $i = 0;
        $iOtro = 0;
        if ($credito->por_anual && $credito->por_anual != 0) {
            //paso 1 : cálculo de cuota ( P*i ) / ( 1 - (1+i)^(-n) )
            $tasaNominalMensual = (pow(1 + ($credito->por_anual / 100), 1 / 12) - 1) * 100;
            // $i = $tasaNominalMensual / 100;
            $i = round($tasaNominalMensual / 100, 4);
            $numerador = ($p * $i);
            $denominador = 1 - pow(1 + $i, -$n);
            $valCuotaFija = $numerador / $denominador;
        } else if ($credito->por_nominal != 0) {
            $tasaNominalMensual = (pow(1 + ($this->calcularAnual($credito->por_nominal) / 100), 1 / 12) - 1) * 100;
            $i = $tasaNominalMensual / 100;
            $numerador = ($p * $i);
            $denominador = 1 - pow(1 + $i, -$n);
            $valCuotaFija = $numerador / $denominador;
        } else if ($credito->otro_por_ea && $credito->otro_por_ea != 0) {
            $otroTasaNominalMensual = (pow(1 + ($credito->otro_por_ea / 100), 1 / 12) - 1) * 100;
            $iOtro = $otroTasaNominalMensual / 100;
            $otroNumerador = ($p * $iOtro);
            $otroDenominador = 1 - pow(1 + $iOtro, -$n);
            $valCuotaFija = $otroNumerador / $otroDenominador;
        } else {
            $valCuotaFija = $p / $n;
        }

        $firmaElec = 0;
        $firmaElecTotal = 0;
        $firmaElecPorcentaje = 0;
        if (!empty($credito->firma_elec) && is_numeric($credito->firma_elec)) {
            $firmaElec = $credito->firma_elec / $n;
            $firmaElecTotal = $credito->firma_elec;
        } else if (!empty($credito->firma_elec_porcentaje) && is_numeric($credito->firma_elec_porcentaje)) {
            $firmaElecPorcentaje = $credito->firma_elec_porcentaje;
            $firmaElecTotal = $p * ($credito->firma_elec_porcentaje / 100);
            $firmaElec = ($p * ($credito->firma_elec_porcentaje / 100)) / $n;
        }
        else if (
            $credito->por_plataforma &&
            $credito->por_plataforma != 0 &&
            $credito->por_plataforma != '' &&
            $credito->por_plataforma != null
        ) {
            $firmaElecTotal = ($p * ($credito->por_plataforma / 100));
            $firmaElec = ($p * ($credito->por_plataforma / 100)) / $n;
        }

        // IVA firma electronica
        $firmaIva = 0;
        $firmaIvaPorcentaje = 0;
        if (!empty($credito->firma_elec_iva) && is_numeric($credito->firma_elec_iva)) {
            $firmaIvaPorcentaje = $credito->firma_elec_iva;
            $firmaIva = $firmaElec * ($credito->firma_elec_iva / 100);
        }

        $otrosValor = 0;
        if (
            $credito->val_otros &&
            $credito->val_otros != 0 &&
            $credito->val_otros != ''
        ) {
            if ($credito->otros_sin_dividir == 1) {
                $otrosValor = $credito->val_otros;
            } else {
                $otrosValor = $credito->val_otros / $n;
            }
        } else if (
            $credito->por_otros &&
            $credito->por_otros != 0 &&
            $credito->por_otros != ''
        ) {
            if ($credito->otros_sin_dividir == 1) {
                $otrosValor = ($p * ($credito->por_otros / 100));
            } else {
                $otrosValor = ($p * ($credito->por_otros / 100)) / $n;
            }
        }

        $tabla[] = array(
            'saldo' => $p,
            'capital' => 0,
            'intereses' => 0,
            'interesesGracia' => 0,
            'otroIntereses' => 0,
            'firmaElec' => 0,
            'firmaIva' => 0,
            'aval' => 0,
            'avalIva' => 0,
            'otros' => 0,
            'valCuotaSinRed' => 0,
            'valCuota' => 0
        );

        $valCuota = 0;
        $saldoGracia = $p;
        $capitalGracia = 0;
        $excencionGracia = $credito->cuotas_liquidez;
        for ($j = 0; $j < $n; $j++) {
            $enGracia = $credito->credito_liquidez == 1;

            // paso 2 : calcúlo de intereses
            if ($j == 0) {
                $proyeccion = CreditoProyeccion::where('credito_id', $credito->id)
                    ->orderBy('fecha')
                    ->first();

                if (isset($proyeccion->valor_cuota)) {
                    // Fecha credito
                    $fechaCredito = Carbon::parse($credito->created_at)->startOfDay();

                    // Dia preferible de pago
                    // $diaPago = Carbon::parse($proyeccion->fecha)->day;

                    // Fecha de pago formateada
                    // $fechaPago = Carbon::createFromDate($fechaCredito->year, $fechaCredito->month, $diaPago)->startOfDay();
                    $fechaPago = Carbon::parse($proyeccion->fecha)->startOfDay();

                    // Si la fecha de pago es menor a la fecha actual se le suma un mes
                    // if ($fechaPago->isPast() && !$fechaPago->isToday()) $fechaPago->addMonth();

                    // Diferencia de días entre la fecha de hoy y la fecha de pago
                    $diasIntereses = (int) $fechaCredito->diffInDays($fechaPago, true);

                    if ($diasIntereses == 0 || $diasIntereses == 31 || ($periodicidad == 2 && $diasIntereses == 15)) {
                        $intereses = ($enGracia ? $saldoGracia : $tabla[$j]['saldo']) * $i;
                        $interesesGracia = $tabla[$j]['saldo'] * $i;
                    } else {
                        $intereses = ((($enGracia ? $saldoGracia : $tabla[$j]['saldo']) * $i) / 30) * $diasIntereses;
                        $interesesGracia = (($tabla[$j]['saldo'] * $i) / 30) * $diasIntereses;
                    }
                } else {
                    $intereses = ($enGracia ? $saldoGracia : $tabla[$j]['saldo']) * $i;
                    $interesesGracia = $tabla[$j]['saldo'] * $i;
                }
            } else {
                $intereses = ($enGracia ? $saldoGracia : $tabla[$j]['saldo']) * $i;
                $interesesGracia = $tabla[$j]['saldo'] * $i;
            }

            $otroIntereses =  ($enGracia ? $saldoGracia : $tabla[$j]['saldo']) * $iOtro;

            // paso 3 : calcúlo de capital
            if ($j == 0) {
                $capital = $valCuotaFija - (($enGracia ? $saldoGracia : $tabla[$j]['saldo']) * $i) - $otroIntereses;
            } else {
                $capital = $valCuotaFija - $intereses - $otroIntereses;
            }
            // paso 4: calcúlo de saldo
            if ($enGracia) {
                $saldoGracia -= $capital; // saldo en gracia
                if ($excencionGracia > $j) {
                    $capitalGracia += round($capital); // capital en gracia
                    $saldo = round($saldoGracia) <= 0 ? $saldoGracia : $p; // saldo inicial del credito
                    $capital = 0; // capital en gracia no se descuenta del saldo, no se abona capital
                } else {
                    $saldo = $saldoGracia;
                }

                // en la última cuota se abona el capital en gracia
                if ($j == $n - 1) $capital += $capitalGracia;
            } else {
                $saldo = $tabla[$j]['saldo'] - $capital;
            }
            // paso 5: calcúlo de cuota:
            if (!is_null($proyeccion->valor_cuota)) {
                $valCuota = $credito->proyecciones[$j]->valor_cuota;
            } else {
                if ($avalTotalizado) {
                    $valCuota = $firmaElec + $firmaIva + $otrosValor + ($enGracia ? ($capital + $interesesGracia) : $valCuotaFija);
                } else {
                    $valCuota = $firmaElec + $firmaIva + $aval + $avalIva + $otrosValor + ($enGracia ? ($capital + $interesesGracia) : $valCuotaFija);
                }
            }

            $tabla[] = array(
                'saldo' => abs($saldo),
                'capital' => $capital,
                'intereses' => $intereses,
                'interesesGracia' => $interesesGracia,
                'otroIntereses' => $otroIntereses,
                'firmaElec' => $firmaElec,
                'firmaIva' => $firmaIva,
                'aval' => $avalTotalizado ? 0 : $aval,
                'avalIva' => $avalTotalizado ? 0 : $avalIva,
                'otros' => $otrosValor,
                'valCuotaSinRed' => $valCuotaFija,
                'valCuota' => $valCuota
            );
        }

        $i = -1;
        foreach ($tabla as $t) {
            $i++;
            $tabla[$i]['saldo'] = round($tabla[$i]['saldo']);
            $tabla[$i]['capital'] = round($tabla[$i]['capital']);
            $tabla[$i]['intereses'] = round($enGracia ? $tabla[$i]['interesesGracia'] : $tabla[$i]['intereses']);
            $tabla[$i]['otroIntereses'] = round($tabla[$i]['otroIntereses']);
            $tabla[$i]['firmaElec'] = round($tabla[$i]['firmaElec']);
            $tabla[$i]['firmaIva'] = round($tabla[$i]['firmaIva']);
            $tabla[$i]['aval'] = round($tabla[$i]['aval']);
            $tabla[$i]['avalIva'] = round($tabla[$i]['avalIva']);
            $tabla[$i]['otros'] = round($tabla[$i]['otros']);
            $tabla[$i]['valCuotaSinRed'] = round($tabla[$i]['valCuotaSinRed']);
            $tabla[$i]['valCuota'] = round($tabla[$i]['valCuota']);
        }

        array_shift($tabla); // Elimina el primer elemento del arreglo

        return $tabla;
    }

    public function calculoLiquidacion($credito, $proyeccion, $modular, $vAbonos) {
        // se obtiene el plan de pagos del credito
        $planPagos = app(MobileController::class)->obtenerPlanDePagos($credito->id, null);
        array_shift($planPagos);// se remueve el primer elemento del array cuyos valores estan en 0

        $hoy = Carbon::now()->endOfDay(); // fecha actual
        $vPagar = 0; // valor a pagar para liquidar el credito hoy

        // valor a favor del cliente
        $favorCliente = $modular ?? 0;

        forEach($proyeccion as $index => $p) {
            $fechaCredito = Carbon::parse($credito->created_at)->endOfDay(); // fecha de colocacion del credito
            $diasCalculo = 0; // dias de intereses a pagar de la cuota pendiente

            // cuota sin pagar
            if ($p->pagado == 0) {
                $fechaPago = Carbon::parse($p->fecha)->endOfDay(); // fecha vencimiento de la cuota (dia de pago de la cuota)
                $diasCuota = $credito->periocidad == 1 ? 30 : 15; // valor a utilizar para calcular intereses proporcionales
                $intereses = 0; // intereses proporcionales que debe abonar el cliente

                // si la cuota pendiente por pagar es la primera
                if ($index == 0) {
                    // fecha a utilizar en la primera cuota para el calculo de intereses (puede ser menor a 30 dias)
                    $diasCuota = (int) $fechaCredito->diffInDays($fechaPago, true);

                    // se valida si la fecha de pago es menor a hoy (cuota en mora)
                    if ($fechaPago->isPast()) {
                        $diasCalculo = (int) $fechaCredito->diffInDays($fechaPago, true);
                    } else {
                        $diasCalculo = (int) $fechaCredito->diffInDays($hoy, true); // al dia
                    }
                } else {
                    // fecha de la cuota anterior a la cuota actual
                    $fechaAnterior = Carbon::parse($proyeccion[$index - 1]->fecha)->endOfDay();

                    // validar si la fecha de pago es menor a hoy (cuota en mora)
                    if ($fechaPago->isPast()) {
                        $diasCalculo = (int)  $fechaAnterior->diffInDays($fechaPago, true);
                    } else if ($fechaAnterior->isPast()) {
                        $diasCalculo = (int) $fechaAnterior->diffInDays($hoy, true); // al dia
                    }
                }

                if ($diasCalculo > 0) {
                    // si el mes es marzo se realiza el calculo de dias de mes hasta maximo 28 dias
                    if ($fechaPago->format('m') == '03') {
                        if ($diasCalculo == 28) {
                            $diasCalculo = 28;
                            $diasCuota = 28;
                        }
                    }

                    // normalizar valores: si son 31, se reemplazan por 30 para realizar el calculo correcto
                    $diasCuota = ($diasCuota == 31) ? 30 : $diasCuota;
                    $diasCalculo = ($diasCalculo == 31) ? 30 : $diasCalculo;

                    $intereses = ($planPagos[$index]['intereses'] / $diasCuota) * $diasCalculo; // calculo intereses proporcionales
                }

                // se resta el total de intereses y se suma el valor proporcional de intereses a pagar de acuerdo al calculo de dias
                $vPagar += $planPagos[$index]['valCuota'] - $planPagos[$index]['intereses'] + round($intereses);
                $vPagar += round($p->intereses_moratorios ?? 0); // intereses moratorios
                $vPagar += round($p->gastos_cobranza ?? 0); // gastos de cobranza
            }
        }

        // si el cliente tiene valores a favor se le restan al valor a pagar
        $vPagar -= $favorCliente;

        if ($vPagar < 0) {
            $saldo = $credito->valor_credito - $vAbonos;
            $vPagar = max(0, $saldo); // si el saldo es menor a 0 se le asigna 0
        }

        // valor total a pagar a hoy (liquidar a hoy)
        return $vPagar;
    }

    function calcularAnual($efectivoNominal) {
        $base = $efectivoNominal + 1;
        $porcentaje = (pow($base, 12) - 1) * 100;
        return $porcentaje;
    }
}
