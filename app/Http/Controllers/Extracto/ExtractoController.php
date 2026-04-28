<?php

namespace App\Http\Controllers\Extracto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Creditos\CreditoController;
use App\Http\Controllers\Mobile\MobileController;
use App\Mail\Extracto;
use App\Models\Abono;
use App\Models\Ciudad;
use App\Models\Cliente;
use App\Models\Condonacion;
use App\Models\CorreosPlantilla;
use App\Models\Credito;
use App\Models\CreditoProyeccion;
use App\Models\Empresa;
use App\Models\InformacionExtracto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ExtractoController extends Controller
{
    public function generate(Request $request, $creditoId)
    {
        Carbon::setLocale('es');

        // Gastos de cobranza e impuestos moratorios ingresados desde la opcion liquidar adicionales, antes de generar el extracto
        $cuotasMora = $request->input('cuotasMora', []);

        // Validar si se debe enviar extracto al correo o mostrar en el navegador
        $enviarCorreo = $request->input('enviarCorreo', false);
        $cuerpoCorreo = $request->input('cuerpoCorreo', '');
        $asuntoCorreo = $request->input('asuntoCorreo', '');

        // Establecer la fecha de corte
        $fechaCorte = isset($_GET['fecha_corte']) ?
            Carbon::parse($_GET['fecha_corte'] . ' 23:59:59') :
            Carbon::now();

        // Obtener el crédito
        $credito = Credito::with('proyecciones')->find($creditoId);
        if ($credito === null) {
            // credito inexistente...
        }

        // Obtener el cliente
        $cliente = Cliente::find($credito->client_id);
        if ($cliente === null) {
            // cliente inexistente...
        }

        // Obtener los datos de la empresa
        $empresa = Empresa::find($credito->empresa_id);

        // validar si la empresa es un aliado o una sede
        if ($empresa->aliado || $empresa->sede) {
            $empresa = Empresa::where('id', $empresa->aliado ?? $empresa->sede)->first();
        }

        if ($empresa === null) {
            // empresa inexistente...
        }
        $ciudadEmpresa = Ciudad::find($empresa->ciudad_id);
        $empresa['ciudad'] = $ciudadEmpresa->toArray();

        // Plan de pagos del crédito con valores calculados
        $planPagos = (new MobileController)->obtenerPlanDePagos($credito->id, true);

        // Definir condiciones comunes para obtener los creditos
        $commonAbonosCondition = function ($query) use ($credito, $fechaCorte) {
            $query->where('credito_id', $credito->id);
            $query->where('created_at', '<=', $fechaCorte);
        };

        // Obtener los abonos realizados a la fecha de corte
        $abonos = Abono::where($commonAbonosCondition)
            ->orderBy('created_at', 'asc')
            ->get();

        // Obtener la suma de los abonos realizados a la fecha de corte
        $abonosSumaTotalACredito = Abono::where($commonAbonosCondition)
            ->orderBy('created_at', 'asc')
            ->where('created_at', '<=', $fechaCorte)
            ->sum('valor');

        // Obtener la suma de los abonos realizados por concepto de gastos de cobranza a la fecha de corte
        $abonosSumTotalGasCobranza = Abono::where($commonAbonosCondition)
            ->orderBy('created_at', 'asc')
            ->where('created_at', '<=', $fechaCorte)
            ->sum('abono_gas_cobranza');

        // Obtener la suma de los abonos realizados por concepto de interes moratorios a la fecha de corte
        $abonosSumTotalIntMora = Abono::where($commonAbonosCondition)
            ->orderBy('created_at', 'asc')
            ->where('created_at', '<=', $fechaCorte)
            ->sum('abono_int_mora');

        // Obtener la proyección del crédito
        $proyeccion = CreditoProyeccion::where('credito_id', $credito->id)
            ->orderBy('fecha', 'asc')
            ->get();
        if ($proyeccion->count() === 0) {
            // proyección inexistente..
        }

        // Valor total firma electronica
        $valFirmElect = array_sum(array_column($planPagos, 'firmaElec'));
        // Valor total aval
        $valAval = $credito->aval_value ?? 0;
        // Valor total aval IVA
        $valAvalIva = $credito->aval_value * (($credito->aval_iva ?? 0) / 100);
        // Valor otros
        $valOtros = array_sum(array_column($planPagos, 'otros'));

        // Datos de la tabla envio
        /* Totales */
        $totalGCobranza = 0; // Total gastos de cobranza
        $totalIntMora = 0; // Total intereses moratorios
        $granTotal = 0; // Importe a pagar

        /* Valores pendientes a pagar a la fecha de corte */
        $numExtracto = 0; // Consecutivo del último abono realizado (Solo se muestra cuando la cuota esta pagada)
        $saldoPagar = 0; // Importe a pagar
        $fechaPago = ''; // Fecha de pago

        // Acumular el valor total de las cuotas pagadas
        $totalCuotasPagadas = 0;
        // Acumula la cantidad de cuotas que se encuentran pagadas
        $cantCuotasPagadas = 0;

        // Fecha posterior mas cercana a la fecha de corte, validando que la cuota no este pagada
        $proximaFechaPago = collect($proyeccion)
            ->filter(fn($cuota) => Carbon::parse($cuota->fecha)->gt($fechaCorte) && $cuota->pagado != 1)
            ->min('fecha');

        // Validacion si el credito ha sido liquidado
        $creditoLiquidado = $proyeccion->filter(fn($cuota) => $cuota->pagado == 1)->count() == $proyeccion->count(); // Se valida si todas las cuotas estan pagadas
        $abonoTotalExiste = Abono::where('credito_id', $credito->id) // Se valida si existe un abono que liquide el credito
            ->where('observaciones', 'like', '%Pago total de crédito%')
            ->where('created_at', '<=', $fechaCorte)
            ->exists();

        // Acumulado de las cuotas asociadas a la proyeccion
        $sumaCuotas = 0;

        // discriminacion de los pagos realizados hasta la fecha (capital)
        $infoAbonos = $this->procesarAbonos($abonos, $planPagos);

        // Se valida si se ha realizado condonaciones al credito
        $valorCondonaciones = Condonacion::whereIn('abono_id', function ($query) use ($credito) {
            $query->select('id')->from('abono')->where('credito_id', $credito->id);
        })
            ->where('created_at', '<=', $fechaCorte)
            ->where('concepto_condonacion', 'credito')
            ->sum('valor_condonado');

        $vAbonosSumaTotalACredito = ($abonosSumaTotalACredito ?? 0) + ($valorCondonaciones ?? 0);

        // validar si el cliente tiene un saldo a su favor, pago parcial
        $sobra = $this->calculoValorAFavor($credito->proyecciones[0]->valor_cuota, $vAbonosSumaTotalACredito, $credito->proyecciones, $credito->val_cuotas) ?? 0;
        $vSobra = $sobra;

        foreach ($proyeccion as $keyCuota => $cuota) {
            $sumaCuotas += $cuota->valor_cuota ?? 0;

            // Se determina si el total de abonos es mayor o igual al valor de la cuota multiplicado por el numero de cuota en la que va
            if (
                ($vAbonosSumaTotalACredito >= (isset($cuota->valor_cuota) ? $sumaCuotas : ($credito->val_cuotas * ($keyCuota + 1)))) || ($creditoLiquidado && $abonoTotalExiste)
            ) {
                $cuota['estado'] = 'Pagada';
                $cuota['_pagado'] = true;
                $sumAbonoPasoaPaso = ($valorCondonaciones ?? 0);
                foreach ($abonos as $keyAbono => $abono) {
                    $sumAbonoPasoaPaso += $abono->valor;
                    if (!isset($cuota['_cuota_pagada_at']) && (($sumAbonoPasoaPaso >= (isset($cuota->valor_cuota) ? $sumaCuotas : ($credito->val_cuotas * ($keyCuota + 1)))) || ($creditoLiquidado && $abonoTotalExiste))) {
                        $totalCuotasPagadas = isset($cuota->valor_cuota) ? $sumaCuotas : $credito->val_cuotas * ($keyCuota + 1);
                        $cantCuotasPagadas++;
                        $cuota['_cuota_pagada_at'] = Carbon::parse($abono->created_at);
                        $cuota['_cuota_pago_obs'] = $abono->observaciones;
                        $cuota['_abono_id'] = $abono->id;
                        $cuota['_abono'] = clone $abono;
                        $cuota['_plan_pagos'] = $planPagos[$keyCuota + 1];

                        // Valores a mostrar en el extracto cuando la cuota esta pagada (para usar las mismas variables cuando el pago es parcial)
                        $cuota['_capital'] = $cuota['_plan_pagos']['capital'];
                        $cuota['_intereses'] = $cuota['_plan_pagos']['intereses'] ?? 0;
                        $cuota['_firmaElec'] = $cuota['_plan_pagos']['firmaElec'] ?? 0;

                        $cuota['_total_abonado'] = $this->getSumPlanCuota($cuota['_plan_pagos']);

                        // El numero del extracto es el consecutivo del ultimo abono realizado de la cuota pagada
                        $numExtracto = $cuota['_abono']['consecutivo'] > $numExtracto ? $cuota['_abono']['consecutivo'] : $numExtracto;

                        // Al ser una cudota ya pagada, se deben mostrar en el extracto todas las cuotas asi sean futuras
                        $cuota['_mes_fecha_corte'] = true;
                    }
                }
            } else if (
                !isset($cuota['_cuota_pagada_at'])
                &&
                $abonosSumaTotalACredito > (isset($cuota->valor_cuota) ? $sumaCuotas : ($credito->val_cuotas * ($keyCuota)))
                &&
                $abonos->count() > 0
            ) {

                // Validacion de si la cuota hace parte del mes de la fecha de corte
                $cuota['_mes_fecha_corte'] = Carbon::parse($cuota['fecha'])->lt(Carbon::parse($fechaCorte)->endOfMonth());

                // Informacion del ultimo abono realizado
                $abonoCuotaParcial = $abonos[$abonos->count() - 1];
                $cuota['_cuota_pagada_at'] = Carbon::parse($abonoCuotaParcial->created_at); // Fecha del abono
                $cuota['_cuota_pago_obs'] = $abonoCuotaParcial->observaciones; // Observacion del abono
                $cuota['_plan_pagos'] = $planPagos[$keyCuota + 1]; // Plan de pagos de la cuota
                $cuota['_abono'] = clone $abonoCuotaParcial;
                $cuota['_abono']['valor'] = null;
                $cuota['_abono']['id'] = null;
                $cuota['_parcial'] = true; // Indica que es un abono parcial
                $cuota['_pagado'] = false; // Indica que la cuota no esta pagada completamente

                // Valores a mostrar en el extracto cuando se realiza un pago parcial de la cuota
                $cuota['_intereses'] = $cuota['_plan_pagos']['intereses'] ?? 0;
                $cuota['_firmaElec'] = $cuota['_plan_pagos']['firmaElec'] ?? 0;
                $cuota['_capital'] = $cuota['_plan_pagos']['capital'] ?? 0;
                $cuota['_avalIva'] = $cuota['_plan_pagos']['avalIva'] ?? 0;
                $cuota['_aval'] = $cuota['_plan_pagos']['aval'] ?? 0;


                if ($fechaCorte->lt(Carbon::parse($cuota['fecha'])) || $fechaCorte->lt($cuota['_cuota_pagada_at'])) {
                    $cuota['estado'] = 'Pendiente'; // Si la fecha de corte es menor a la fecha de la cuota, el estado es pendiente

                    // El total abonado de la cuota es la suma del capital, intereses y firma electronica
                    $cuota['_total_abonado'] = $cuota['_plan_pagos']['valCuota'];
                    $cuota['_total_abonado'] -= $vSobra; // Se descuenta el valor a favor al proximo valor a pagar por parte del cliente
                    $vSobra = 0; // Se reinicia el valor a favor al siguiente ciclo

                    // Saldo a pagar y valor a pagar de la siguiente cuota pendiente
                    if (Carbon::parse($cuota['fecha'])->eq($proximaFechaPago)) {
                        $saldoPagar += $cuota['_total_abonado']; // Saldo a pagar del credito
                        $fechaPago = $cuota->fecha; // Fecha de pago
                    }
                } else {
                    $cuota['estado'] = 'No reporta pago'; // Si la fecha de corte es mayor a la fecha de la cuota, el estado es no reporta pago

                    // El total abonado de la cuota es la suma del capital, intereses y firma electronica
                    $cuota['_total_abonado'] = $cuota['_plan_pagos']['valCuota'];
                    $cuota['_total_abonado'] -= $vSobra; // Se descuenta el valor a favor al proximo valor a pagar por parte del cliente
                    $vSobra = 0; // Se reinicia el valor a favor al siguiente ciclo

                    $cuota['_abono']['abono_gas_cobranza'] = null;
                    $cuota['_abono']['abono_int_mora'] = null;

                    // Seteo de gastos de cobranza e intereses moratorios en caso de que existan cuotas en mora
                    $cuotaMora = collect($cuotasMora)->firstWhere('cuota', $keyCuota + 1);

                    if ($cuotaMora) {
                        $cuota['_abono']['abono_gas_cobranza'] = $cuotaMora['gastosCobranza'] ?? null;
                        $cuota['_abono']['abono_int_mora'] = $cuotaMora['impMoratorios'] ?? null;

                        $totalGCobranza += $cuota['_abono']['abono_gas_cobranza'] ?? 0;
                        $totalIntMora += $cuota['_abono']['abono_int_mora'] ?? 0;
                    }

                    $saldoPagar += $cuota['_total_abonado'];
                }
            } else {
                $cuota['_pagado'] = false; // Indica que la cuota no esta pagada
                $cuota['_plan_pagos'] = $planPagos[$keyCuota + 1];

                /**
                 * Validacion de si la cuota hace parte del mes de la fecha de corte o
                 * si la fecha de la cuota es del mes siguiente y el extracto se genera en los ultimos 5 dias del mes
                 */
                $finDeMes = Carbon::now()->endOfMonth();
                $ultimosCincoDias = $finDeMes->copy()->subDays(4)->startOfDay();

                $cuotaFecha = Carbon::parse($cuota['fecha']);
                $mesSiguiente = Carbon::now()->addMonth()->month;

                $cuota['_mes_fecha_corte'] =
                    $cuotaFecha->lt(Carbon::parse($fechaCorte)->endOfMonth()) ||
                    (now()->between($ultimosCincoDias, $finDeMes) && $cuotaFecha->month === $mesSiguiente);

                // Valores a mostrar en el extracto cuando se realiza un pago parcial de la cuota
                $cuota['_intereses'] = $cuota['_plan_pagos']['intereses'] ?? 0;
                $cuota['_firmaElec'] = $cuota['_plan_pagos']['firmaElec'] ?? 0;
                $cuota['_capital'] = $cuota['_plan_pagos']['capital'] ?? 0;

                if ($fechaCorte->gt(Carbon::parse($cuota['fecha']))) {
                    $cuota['estado'] = 'No reporta pago';

                    $cuota['_abono'] = clone $abonos;
                    $cuota['_abono']['consecutivo'] = null;
                    $cuota['_abono']['abono_int_mora'] = null;
                    $cuota['_abono']['abono_gas_cobranza'] = null;

                    // Seteo de gastos de cobranza e intereses moratorios en caso de que existan cuotas en mora
                    $cuotaMora = collect($cuotasMora)->firstWhere('cuota', $keyCuota + 1);

                    if ($cuotaMora) {
                        $cuota['_abono']['abono_gas_cobranza'] = $cuotaMora['gastosCobranza'] ?? null;
                        $cuota['_abono']['abono_int_mora'] = $cuotaMora['impMoratorios'] ?? null;

                        $totalGCobranza += $cuota['_abono']['abono_gas_cobranza'] ?? 0;
                        $totalIntMora += $cuota['_abono']['abono_int_mora'] ?? 0;
                    }

                    // Total adeudado de la cuota mas los intereses corrientes
                    $cuota['_total_adeudado'] = $this->getSumPlanCuota($cuota['_plan_pagos']);
                    $cuota['_total_adeudado'] -= $vSobra; // Se descuenta el valor a favor al proximo valor a pagar por parte del cliente
                    $vSobra = 0; // Se reinicia el valor a favor al siguiente ciclo

                    $saldoPagar += $cuota['_total_adeudado']; // Saldo a pagar del credito
                } else {
                    $cuota['estado'] = 'Pendiente';

                    // Total adeudado de la cuota mas los intereses corrientes
                    $cuota['_total_adeudado'] = $this->getSumPlanCuota($cuota['_plan_pagos']);
                    $cuota['_total_adeudado'] -= $vSobra; // Se descuenta el valor a favor al proximo valor a pagar por parte del cliente
                    $vSobra = 0; // Se reinicia el valor a favor al siguiente ciclo

                    if (Carbon::parse($cuota['fecha'])->eq($proximaFechaPago)) {
                        $saldoPagar += $cuota['_total_adeudado']; // Saldo a pagar del credito
                        $fechaPago = $cuota->fecha; // Fecha de pago
                    }
                }
            }
        }

        foreach ($proyeccion as $keyCuota => $cuota) {
            if (isset($cuota['_cuota_pagada_at'])) {
                $prevsWithSameAbono = $proyeccion->filter(
                    fn($_c, $_ci) => $_ci < $keyCuota && isset($_c['_abono_id']) && $_c['_abono_id'] === $cuota['_abono_id']
                );
                $postsWithSameAbono = $proyeccion->filter(
                    fn($_c, $_ci) => $_ci > $keyCuota && isset($_c['_abono_id']) && $_c['_abono_id'] === $cuota['_abono_id']
                );
                $cuota['_prevs_has_same_abono'] = $prevsWithSameAbono->count() > 0;
                $cuota['_post_has_same_abono'] = $postsWithSameAbono->count() > 0;
                if (!$cuota['_prevs_has_same_abono'] && $cuota['_post_has_same_abono']) {
                    $cuota['rowspan'] = $postsWithSameAbono->count() + 1;
                    $cuota['_total_abonado'] = $postsWithSameAbono->reduce(function ($carry, $_c) {
                        return $this->getSumPlanCuota($_c['_plan_pagos']) + $carry;
                    }, 0) +
                        $this->getSumPlanCuota($cuota['_plan_pagos']) +
                        ($abono->abono_gas_cobranza ?? 0) +
                        ($abono->abono_int_mora ?? 0);
                } else if ($cuota['_prevs_has_same_abono']) {
                    $cuota['rowspan'] = 0;
                } else {
                    $cuota['rowspan'] = 1;
                }
            }
        }

        // Definir la configuración de las columnas
        $config = [
            'columns' => [
                'fecha_cuota' => true,
                'fecha_transaccion' => true,
                'consecutivo_abono' => true,
                'estado' => true,
                'descripcion' => true,
                'capital' => true,
                'int_corriente' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_plan_pagos']) &&
                        isset($cuota['_plan_pagos']['intereses']) &&
                        $cuota['_plan_pagos']['intereses'] > 0;
                }),
                'firma_elec' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_plan_pagos']) &&
                        isset($cuota['_plan_pagos']['firmaElec']) &&
                        $cuota['_plan_pagos']['firmaElec'] > 0;
                }),
                'otros' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_plan_pagos']) &&
                        isset($cuota['_plan_pagos']['otros']) &&
                        $cuota['_plan_pagos']['otros'] > 0;
                }),
                'otro_interes' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_plan_pagos']) &&
                        isset($cuota['_plan_pagos']['otroIntereses']) &&
                        $cuota['_plan_pagos']['otroIntereses'] > 0;
                }),
                'aval' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_plan_pagos']) &&
                        isset($cuota['_plan_pagos']['aval']) &&
                        $cuota['_plan_pagos']['aval'] > 0;
                }),
                'avalIva' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_plan_pagos']) &&
                        isset($cuota['_plan_pagos']['avalIva']) &&
                        $cuota['_plan_pagos']['avalIva'] > 0;
                }),
                'int_mora' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_abono']) &&
                        isset($cuota['_abono']['abono_int_mora']) &&
                        $cuota['_abono']['abono_int_mora'] > 0;
                }),
                'gas_cobranza' => $proyeccion->some(function ($cuota) {
                    return isset($cuota['_abono']) &&
                        isset($cuota['_abono']['abono_gas_cobranza']) &&
                        $cuota['_abono']['abono_gas_cobranza'] > 0;
                }),
                'total' => true,
            ],
        ];
        $config['num_columns_visible'] = collect($config['columns'])->filter(fn($col) => $col === true)->count();
        $config['num_columns_concepts_credit_visible'] = collect([
            $config['columns']['capital'],
            $config['columns']['int_corriente'],
            $config['columns']['firma_elec'],
            $config['columns']['otros'],
            $config['columns']['otro_interes'],
            $config['columns']['aval'],
            $config['columns']['avalIva'],
        ])->filter(fn($col) => $col === true)->count();

        // valor total de la compra sin intereses (valor_base del credito + firma electronica + aval + otros)
        $valorCompra = $credito->valor_base + $valAval + $valAvalIva + $valOtros + $valFirmElect;

        $granTotal = $saldoPagar + $totalIntMora + $totalGCobranza;

        $totalAbonadoFechaCorte = array_reduce($infoAbonos, function ($carry, $item) {
            // return $carry + ($item['detalles']['capital'] ?? 0); // total abonado a capital hasta la fecha de corte
            return $carry + (($item['valorAbono'] ?? 0) - ($item['detalles']['intereses'] ?? 0)); // total abonado sin intereses hasta la fecha de corte
        }, 0);

        // si el credito esta finalizado y la fecha de cierre es menor a la fecha de corte
        if ($credito->fecha_cierre && $credito->fecha_cierre <= $fechaCorte) {
            $saldoCredito = 0;
        } else {
            // $saldoCredito = max(0, $credito->valor_credito - ($abonos->sum('valor') + ($valorCondonaciones ?? 0))); // calcular saldo del credito a la fecha con intereses
            // $saldoCredito = max(0, $valorCompra - ($totalAbonadoFechaCorte + ($valorCondonaciones ?? 0))); // calcular saldo del credito a la fecha sin intereses

            // saldo total pendiente
            $saldoCredito = (new CreditoController)->calculoLiquidacion($credito, $credito->proyecciones, $sobra, $vAbonosSumaTotalACredito);
        }

        // Informacion personalizada del extracto
        $logo = null;
        $infoPersonalizadaExtracto = InformacionExtracto::where('empresa_id', $empresa->id)->first();
        $colorBase = $infoPersonalizadaExtracto ? $infoPersonalizadaExtracto->color_base . ';' : '#a00c6c;';
        if ($empresa->logo)
            $logo = Storage::disk('s3')->temporaryUrl($empresa->logo, Carbon::now()->addMinutes(30));

        $pdf = Pdf::loadView('pdf.extracto', compact(
            'proyeccion',
            'config',
            'abonosSumaTotalACredito',
            'empresa',
            'credito',
            'cliente',
            'fechaCorte',
            'totalAbonadoFechaCorte',
            'saldoCredito',
            'saldoPagar',
            'fechaPago',
            'numExtracto',
            'totalIntMora',
            'totalGCobranza',
            'granTotal',
            'infoPersonalizadaExtracto',
            'colorBase',
            'logo',
            'valorCompra'
        ));

        $fileName = $credito->id . '/extracto' . $credito->id . '_' . $fechaCorte->format('Y-m-d_H:i:s') . '.pdf';

        // return $pdf->stream();

        Storage::disk('s3')->put($fileName, $pdf->output());
        $expiracion = Carbon::now()->addMinutes(30); // Establecer la expiración en 5 minutos
        $url = Storage::disk('s3')->temporaryUrl($fileName, $expiracion);

        if ($enviarCorreo) {
            $cuerpoFormateado = preg_replace([
                '/class="([^"]*)\bql-align-center\b([^"]*)"/',
                '/class="([^"]*)\bql-align-right\b([^"]*)"/',
                '/class="([^"]*)\bql-align-justify\b([^"]*)"/'
            ], [
                'style="text-align: center;"',
                'style="text-align: right;"',
                'style="text-align: justify;"'
            ], $cuerpoCorreo);

            Mail::to($request->email)->send(new Extracto($cuerpoFormateado, $url, $asuntoCorreo, $fileName));
        } else {
            return $url;
        }
    }

    protected function getSumPlanCuota($plan)
    {
        // return $plan['capital'] + $plan['intereses'] + $plan['otroIntereses'] + $plan['firmaElec'] + $plan['otros'] + $plan['aval'] + $plan['avalIva'];
        return $plan['valCuota'];
    }

    protected function getSumPlanCuotaSinIntereses($plan)
    {
        // return $plan['capital'] + $plan['otroIntereses'] + $plan['firmaElec'] + $plan['otros'] + $plan['aval'] + $plan['avalIva'];
        return $plan['valCuota'] - $plan['intereses'];
    }

    public function liquidarAdicionales($creditoId)
    {
        $fechaCorte = isset($_GET['fecha_corte']) ? Carbon::parse($_GET['fecha_corte'] . ' 23:59:59') : Carbon::now();

        $cuotasEnMora = [];

        $proyeccion = CreditoProyeccion::where('credito_id', $creditoId)
            ->where('fecha', '<=', $fechaCorte)
            ->orderBy('fecha', 'asc')
            ->get();

        foreach ($proyeccion as $index => $cuota) {
            if (Carbon::parse($fechaCorte)->gt(Carbon::parse($cuota['fecha'])) && $cuota->pagado == 0) {
                $cuotasEnMora[] = (object) [
                    'num_cuota' => $index + 1,
                    'cuota' => $cuota
                ];
            }
        }

        return response()->json($cuotasEnMora);
    }

    public function emailDataInfo(Request $request, $creditoId)
    {
        $empresaId = auth()->user()->empresa_id;

        // Datos del cliente
        $cliente = Credito::find($creditoId)
            ->cliente()
            ->select('*', 'email as correo', 'telEmpresa as tel_empresa', 'direccionEmpresa as dir_empresa', 'fecha_nacimiento as nacimiento', 'empresa_labora as empresa', 'created_at as registro')
            ->first();

        // validar si la empresa es principal o es una sede/aliado
        $empresaPrincipal = Empresa::find($empresaId);
        if ($empresaPrincipal->aliado || $empresaPrincipal->sede) {
            $empresaPrincipal = Empresa::find($empresaPrincipal->aliado ?? $empresaPrincipal->sede);
        }

        // Datos de la empresa
        $empresa = Empresa::select('empresa.*', 'telefonoComercial as tel_comercial', 'ciudad.nombre as ciudad')
            ->leftJoin('ciudad', 'empresa.ciudad_id', '=', 'ciudad.id')
            ->where('empresa.id', $empresaPrincipal->id)
            ->first();

        // Datos del crédito
        $credito = Credito::select('*', 'val_cuotas as val_cuota')
            ->where('id', $creditoId)
            ->first();

        // Plantilla de correo predeterminada
        $plantilla = CorreosPlantilla::where('empresa_id', $empresaPrincipal->id)
            ->where(function ($query) {
                $query->where('nombre', 'Extracto')
                    ->orWhere('nombre', 'extracto');
            })
            ->first();

        return response()->json([
            'plantilla' => $plantilla,
            'cliente' => $cliente,
            'credito' => $credito,
            'empresa' => $empresa
        ]);
    }

    public function procesarAbonos($abonos, $planPagos)
    {
        $indicePlan = 0;
        $abonosAsociados = [];

        $planPagosAbonos = $planPagos;
        array_shift($planPagosAbonos); // Se elimina el primer elemento cuyos valores se encuentran en 0

        // Se itera el plan de pagos y los abonos realizados para determinar los detalles de cada abono
        foreach ($abonos as $a) {
            $valorPendiente = $a->valor; // Valor del abono actual (Se itera tanto el abono actual como los abonos anteriores)
            $detalleAbono = ['intereses' => 0, 'firmaElec' => 0, 'aval' => 0, 'avalIva' => 0, 'capital' => 0, 'otros' => 0];

            while ($indicePlan < count($planPagosAbonos)) {
                $p = &$planPagosAbonos[$indicePlan];

                if ($valorPendiente <= 0)
                    break;

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
        }

        return $abonosAsociados;
    }

    public function calculoValorAFavor($valorCuota, $abonos, $proyeccion, $creditoValCuota)
    {
        $valorAFavor = 0;
        if (isset($valorCuota)) { // Se valida si el credito tiene un valor personalizado para la primera cuota
            $sumaPagos = 0;
            if ($abonos > 0) { // Se valida que se hayan realizado abonos
                foreach ($proyeccion as $p) {
                    $sumaPagos += $p->valor_cuota; // Se suman los valores de las cuotas pagadas
                    if ($p->pagado == 0) {
                        $valorAFavor = $p->valor_cuota - ($sumaPagos - $abonos);
                        break;
                    }
                }
            }
        } else {
            $valorAFavor = $abonos % $creditoValCuota; // Operacion por defecto utilizada cuando todas las cuotas tenian el mismo valor
        }

        return $valorAFavor;
    }
}
