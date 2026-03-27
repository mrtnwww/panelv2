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
use App\Models\ParametrosInterese;
use App\Models\Persona;
use App\Models\ReporteCentralesHistorial;
use App\Models\TipoPago;
use App\Models\Usuario;
use App\Traits\CalculoCobranza;
use App\Traits\CalculoPagoMinimo;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditoController extends Controller
{
    use CalculoCobranza;
    use CalculoPagoMinimo;

    public function creditDetails(Request $request)
    {
        $empresaId = $request->user()?->empresa_id;

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

    public function creditsCobranza(Request $request)
    {
        $empresaId = $request->user()?->empresa_id;

        $currentEmpresaId = $empresaId;

        // Empresas (aliados y sedes) asociadas en una sola consulta
        $empresasAliados = Empresa::where('aliado', $currentEmpresaId)
            ->orWhere('sede', $currentEmpresaId)
            ->pluck('id')
            ->toArray();

        $empresasAliados[] = $currentEmpresaId;

        // Término de búsqueda
        $searchTerm = $request->input('search', '');
        // condiciones de busqueda
        $conditions = $request->input('conditions', []);
        // condiciones de busqueda especificas para el modulo de cobranza
        $conditionsCobranza = $request->input('conditionsCobranza', []);

        // creditos por pagina
        $perPage = $request->input('per_page', 10);

        $query = Credito::whereIn('empresa_id', $empresasAliados)
            ->where('created_at', '<', Carbon::yesterday()->endOfDay())
            ->applyConditionsCobranza($conditionsCobranza)
            ->applyConditions($conditions)
            ->applySearch($searchTerm, null)
            ->with([
                'cliente:id,nombre,cedula,ciudad,direccion,email,telefono,estado_cliente_tarea,fecha_fin_acuerdo_pago',
                'cliente.ciudadInfo:id,nombre',
                'proyecciones',
                'abonos',
                'notificaciones',
                'empresa:id,razon_social'
            ])
            ->orderBy('id', 'desc');

        $allCreditosIds = $query->pluck('id')->toArray();

        // creditos paginados
        $creditos = $query->paginate($perPage);
        $creditoIds = $creditos->pluck('id')->toArray();

        $data = $this->procesarCreditosCobranza($creditos, $creditoIds);

        return response()->json([
            'creditos' => $data,
            'allCreditosIds' => $allCreditosIds
        ]);
    }

    private function procesarCreditosCobranza($creditos, $ids) {
        $hoy = Carbon::now();

        // Ultimos reportes de los créditos
        $ultReportes = ReporteCentralesHistorial::select(
                'reporte_centrales_historial.created_at',
                'reporte_centrales_historial.tipo_reporte_id',
                'reporte_centrales_tipo.tipo_reporte AS tipo_reporte_nombre',
                'reporte_centrales_historial.credito_id'
            )
            ->whereIn('credito_id', $ids)
            ->orderBy('reporte_centrales_historial.created_at', 'desc')
            ->join('reporte_centrales_tipo', 'reporte_centrales_tipo.id', '=', 'reporte_centrales_historial.tipo_reporte_id')
            ->get()
            ->groupBy('credito_id');

        // Condonaciones agrupadas por credito
        $condonaciones = Condonacion::join('abono', 'abono.id', '=', 'condonaciones.abono_id')
            ->whereIn('abono.credito_id', $ids)
            ->where('condonaciones.concepto_condonacion', 'credito')
            ->select(DB::raw('SUM(condonaciones.valor_condonado) as total, abono.credito_id'))
            ->groupBy('abono.credito_id')
            ->pluck('total', 'credito_id');

        foreach ($creditos as $credito) {
            $cliente = $credito->cliente;
            $cliente->ciudad_nombre = $cliente->ciudadInfo ? $cliente->ciudadInfo->nombre : '';

            // Determinar si el crédito está finalizado hoy
            $estaFinalizadoHoy = !$credito->proyecciones->where('pagado', 0)->isNotEmpty();

            // Obtener la última notificación
            $ultVezNotificado = $credito->notificaciones
                ->whereNotNull('correo_id')
                ->sortByDesc('created_at')
                ->first();

            $fechaNotificado = $ultVezNotificado ? Carbon::parse($ultVezNotificado->created_at) : '';
            $notificado = $ultVezNotificado && $fechaNotificado->between($hoy->startOfDay(), $hoy->endOfDay());

            $ultReporte = $ultReportes->get($credito->id);
            $ultReporte = $ultReporte ? $ultReporte->first() : null;

            $infoUltReporte = [
                "fecha"   => $ultReporte ? $ultReporte->created_at : '',
                "tipo_id" => $ultReporte ? $ultReporte->tipo_reporte_id : '',
                "tipo"    => $ultReporte ? $ultReporte->tipo_reporte_nombre : '',
            ];

            $credito->cliente = $cliente;
            $credito->notificacion = [
                'notificado' => $notificado,
                'fecha' => $fechaNotificado,
            ];
            $credito->notificado = $notificado;
            $credito->infoUltReporte = $infoUltReporte;
            $credito->estaFinalizadoHoy = $estaFinalizadoHoy;
            $credito->razon_social = $credito->empresa->razon_social ?? '';

            $proyecciones = $credito->proyecciones;
            $abonos = $credito->abonos->sum('valor');

            // Calculo a favor del cliente
            $modular = $this->calculoValorAFavor($proyecciones[0]->valor_cuota, $abonos, $proyecciones, $credito) ?? 0;

            // Calcular el valor en mora del credito
            $credito->valorMinPago = $this->pagoMinimo($credito->proyecciones, $modular, $credito->val_cuotas) ?? 0;

            // Validar si se han realizado condonaciones al credito
            $credito->valorCondonadoCredito = (int) ($condonaciones[$credito->id] ?? 0);
        }

        return $creditos;
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

    public function calculoValorAFavor($valorCuota, $abonos, $proyeccion, $credito)
    {
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

    function CalcularCapital($id)
    {
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

    public function calculoLiquidacion($credito, $proyeccion, $modular, $vAbonos)
    {
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

    function calcularAnual($efectivoNominal)
    {
        $base = $efectivoNominal + 1;
        $porcentaje = (pow($base, 12) - 1) * 100;
        return $porcentaje;
    }

    function detailCredit(Request $request)
    {
        $usuarioId = $request->user()?->id;
        $empresaId = $request->user()?->empresa_id;

        $user = Usuario::where('id', $usuarioId)->first();

        $credito = Credito::find($request['credito_id']);

        // Calculo gastos de cobranza e intereses moratorios
        $vCreditoId = array();
        $vCreditoId[] = $credito->id;
        if (!$credito->fecha_gcobranza || Carbon::parse($credito->fecha_gcobranza)->isToday() == false) {
            $this->calculoCobranzaIntMora($vCreditoId);
            $credito->fecha_gcobranza = Carbon::now();
            $credito->save();
        }

        //buscar abonos o pagos
        $conditions1 = [
            ['credito_id', $credito->id]
        ];

        $abonos = Abono::where($conditions1)->get();

        // UTC -5 de la fecha de realizacion del abono
        foreach ($abonos as $abono) {
            $abono->fecha_abono = Carbon::parse($abono->created_at)->subHours(5)->format('Y-m-d H:i:s');
        }

        $condonaciones = [];
        foreach($abonos as $abono){
            foreach($abono->condonaciones as $condonacion){
                $persona = Persona::find($condonacion->usuario->persona_id);
                $condonacion->usuario_nombre = $persona->nombre;

                $condonaciones[] = $condonacion;
            }
        }

        // condonaciones por credito
        $condonacionesTareas = Condonacion::where('credito_id', $credito->id)->get();
        foreach($condonacionesTareas as $condonacion){
            $persona = Persona::find($condonacion->usuario->persona_id);
            $condonacion->usuario_nombre = $persona->nombre;

            $condonaciones[] = $condonacion;
        }

        //$totalAbonos = Abono::where($conditions1)->sum('valor');
        $cantidadPagas = CreditoProyeccion::where([[$conditions1],['pagado' , '1']])->count();
        $totalAbonos = $credito->val_cuotas * $cantidadPagas;

        $proyeccion = CreditoProyeccion::where($conditions1)->get();

        $cuotasPagadas = $totalAbonos / $credito->val_cuotas;
        $explode = explode(".", $cuotasPagadas);
        // Acá debe ir el tema de calcular el crédito para calcular el valor a dia de hoy

        $cliente = Cliente::where('id', $credito->client_id)->first();
        $empresa = Empresa::where('id', $cliente['empresa_id'])->first();
        $parametrosIntereses = ParametrosInterese::withTrashed()
            ->where('empresa_id', $cliente['empresa_id'])
            ->where('lineas_credito_id', $credito->lineas_credito_id)
            ->first();

        if ($empresa->aliado) {
            $parametrosIntereses = ParametrosInterese::withTrashed()
                ->where('empresa_id', $empresa->aliado)
                ->where('lineas_credito_id', $credito->lineas_credito_id)
                ->first();
        }
        if ($empresa->sede) {
            $parametrosIntereses = ParametrosInterese::withTrashed()
                ->where('empresa_id', $empresa->sede)
                ->where('lineas_credito_id', $credito->lineas_credito_id)
                ->first();
        }

        $arrayGenerico = array();
        $valorCompra = $credito->valor_compra;
        $porNominal = $parametrosIntereses->interes_nm / 100;

        $otros = 0;
        if ($parametrosIntereses->otrosPorcentaje != 0) {
            $otros = ($parametrosIntereses->otrosPorcentaje / 100) * $credito->valor_compra;
        } else if ($parametrosIntereses->otrosNominal) {
            $otros = $parametrosIntereses->otrosNominal;
        }

        $arrayGenerico[] = array(
            "saldo" => number_format($credito->valor_compra),
            "capital" => 0,
            "intereses" => 0,
            "plataforma" => 0,
            "otros" => 0,
            "iva" => 0,
            "cuota" => 0,
            "porPlataforma" => $empresa->porcentaje
        );

        $numCuotas = $credito->num_cuotas;
        $capital = $credito->valor_compra / $numCuotas;

        $totales = array(
            "capital" => 0,
            "intereses" => 0,
            "valorOtros" => 0,
            "valorCuota" => 0,
            "total" => 0
        );

        for ($i = 0; $i < $numCuotas; $i++) {
            //echo nl2br(round($valorCompra) ."\n");
            if ($i == 0 && isset($proyeccion[0]['valor_cuota'])) {
                // Calcular los intereses de la primera cuota
                $fechaCreacion = Carbon::parse($credito->created_at)->startOfDay();
                $fechaPrimeraCuota = Carbon::parse($proyeccion[0]->fecha);
                $diferenciaDias = $fechaCreacion->diffInDays($fechaPrimeraCuota);
                $intereses = ((round($valorCompra) * $porNominal) / 30) * $diferenciaDias;
            } else {
                $intereses = round($valorCompra) * $porNominal;
            }
            $plataforma = 0;
            $plataforma = $credito->valor_compra * ($empresa->porcentaje / 100) / $numCuotas;
            $valorOtros = $otros / $numCuotas;
            $valorCuotas = round($capital) + round($intereses) + round($plataforma) + round(($otros / $numCuotas));
            //echo nl2br(round($valorCuotas) ." = ". round($capital) ." + ". round($intereses) ." + ". round($plataforma) ." + ". round(($otros / $numCuotas)) ."\n");
            $valorCompra -= $capital;
            if ($valorCompra < 0) {
                $valorCompra = 0;
            }

            $totales["capital"] +=  $capital;
            $totales["intereses"]  += $intereses;
            $totales["valorOtros"]  += $valorOtros;
            $totales["valorCuota"]  += $valorCuotas;
            $totales["total"]  += $valorCuotas;

            $capital = round($valorCuotas) - round($valorOtros) - round($intereses) - round($plataforma);
        }

        $valorCuotaNuevo = $totales["total"] / $numCuotas;

        $formatoMoneda = number_format($valorCuotaNuevo);
        $centimos = explode(',', $formatoMoneda);

        if (count($centimos) > 1) {
            $centimos[count($centimos) - 1] = floor((($centimos[count($centimos) - 1]) / 100));
            $centimos[count($centimos) - 1] = $centimos[count($centimos) - 1] * 100;
            if ($centimos[count($centimos) - 1] == 0) {
                $centimos[count($centimos) - 1] = "000";
            }
        }


        $aproximado = "";
        foreach ($centimos as $cent) {
            $aproximado .= $cent;
        }

        $totales = array(
            "capital" => 0,
            "intereses" => 0,
            "valorOtros" => 0,
            "plataforma" => 0,
            "valorCuota" => 0,
            "total" => 0
        );

        $contador = $numCuotas;
        if ($credito->periocidad == 2) {
            $contador = $contador * 2;
        }

        $cuota = 0;
        $plataforma = 0;
        $saldoB = $credito->valor_compra;
        $otrosPintar = $otros;


        for ($i = 0; $i < $contador; $i++) {
            $plataforma = $credito->valor_compra * ($empresa->porcentaje / 100) / $contador;
            if ($i == 0 && isset($proyeccion[0]['valor_cuota'])) {
                // Calcular los intereses de la primera cuota
                $fechaCreacion = Carbon::parse($credito->created_at)->startOfDay();
                $fechaPrimeraCuota = Carbon::parse($proyeccion[0]->fecha);
                $diferenciaDias = $fechaCreacion->diffInDays($fechaPrimeraCuota);
                $intereses = (($saldoB * $porNominal) / 30) * $diferenciaDias;
            } else {
                $intereses = $saldoB * $porNominal;
            }
            $otrosPintar = $otros / $numCuotas;

            $cuota = $aproximado;
            if ($credito->periocidad == 2) {
                $cuota = $cuota / 2;
                $intereses = $intereses / 2;
                $otrosPintar = $otrosPintar / 2;
            }

            $capital = $cuota - $plataforma - $intereses - $otrosPintar;
            $saldoB -= $capital;
            if ($saldoB < 0) {
                $saldoB = 0;
            }

            //echo nl2br($saldoB ." ".$capital . " ". $intereses . " ".$plataforma . " ". $otrosPintar. "0 ".$cuota. "\n");

            $arrayGenerico[] = array(
                "saldo" => $saldoB,
                "capital" => $capital,
                "intereses" => $intereses,
                "plataforma" => $plataforma,
                "otros" => $otrosPintar,
                "iva" => 0,
                "cuota" => $cuota
            );


            $totales['capital'] += $capital;
            $totales['intereses'] += $intereses;
            $totales['plataforma'] += $plataforma;
            $totales['valorCuota'] += $cuota;
            $totales['valorOtros'] += $otrosPintar;
        }

        $totales['total'] = $totales['capital']
            + $totales['intereses']
            + $totales['plataforma']
            + $totales['valorCuota']
            + $totales['capital'];

        // valor total del credito menos lo abonado por el cliente
        // $valorPendiente = $credito->valor_credito - $totalAbonos;
        // valor del credito sin intereses futuros
        // Se valida si se ha realizado condonaciones al credito
        $valorCondonaciones = Condonacion::whereIn('abono_id', function ($query) use($credito) {
            $query->select('id')->from('abono')->where('credito_id', $credito->id);
        })->where('concepto_condonacion', 'credito')->sum('valor_condonado');

        $vAbonos = $abonos->sum('valor') + ($valorCondonaciones ?? 0);
        $modular = $this->calculoValorAFavor($proyeccion[0]->valor_cuota, $vAbonos, $proyeccion, $credito) ?? 0;
        $valorPendiente = $this->calculoLiquidacion($credito, $proyeccion, $modular, $vAbonos);

        $i = -1;
        $proyeccionPos = 0;
        foreach ($abonos as $abono) {
            $i++;
            $tipoPago = TipoPago::where('id', $abono->tipo_pago)->first();
            $hechoPorU = Usuario::where('id', $abono->user_id)->withTrashed()->first();
            $hechoPor = Persona::where('id', $hechoPorU->persona_id)->first();

            $abonos[$i]['pagado'] = $tipoPago->nombre;
            $abonos[$i]['hecho'] = $hechoPor->nombre;

            for ($j = 0; $j < $numCuotas; $j++) {
                $cuotasPagadas = ($proyeccionPos + $j);
            }

            $proyeccionPos += $numCuotas; // Actualizar $proyeccionPos para la siguiente iteración
        }

        $posDetenida = 0;
        $sumaInteresTemp = 0;
        $hoy = \Carbon\Carbon::now();
        $i = -1;
        $proyeccionR = CreditoProyeccion::where('credito_id', $credito->id)->get();
        foreach ($proyeccionR as $p) {
            $i++;
            if ($p->pagado == 0) {
                $datetime1 = new DateTime($hoy);
                $datetime2 = new DateTime($p->fecha);
                $interval = $datetime1->diff($datetime2);
                $omg = $interval->format('%a');
                $intereses = $arrayGenerico[$i]["intereses"] / 30;
                $sumaInteresTemp += ($arrayGenerico[$i]["intereses"] - ($intereses * $omg));
                $posDetenida = $i;
                break;
            }
        }

        for ($i = ($posDetenida); $i < count($proyeccion); $i++) {
            $sumaInteresTemp += $arrayGenerico[($i)]["intereses"];
        }

        $proyeccion = CreditoProyeccion::where('credito_id', $credito->id)
            ->orderBy('fecha', 'desc')
            ->first();
        $mora = 0;
        $moratorio = false;
        if ($proyeccion->diasMora > 0) {

            // $valorPendiente += $proyeccion->diasMora * 100;

            $mora = $proyeccion->diasMora * 100;

            //echo $mora." = ".$proyeccion->diasMora." * 100"."<br>";

            $moratorio = true;
        } else {
            // $valorPendiente -= abs($sumaInteresTemp);
        }


        $proyecciones = CreditoProyeccion::where('credito_id', $credito->id)
            //->where('diasMora', '>', 0)
            //->where('pagado', 0)
            ->get();
        // Validar el valor de la primera cuota
        $primeraProyeccion = $proyecciones->sortBy('fecha')->first();
        $valorPrimerCuota = ($primeraProyeccion->pagado == 0 && isset($primeraProyeccion->valor_cuota))
            ? $primeraProyeccion->valor_cuota
            : null;

        $isCuota = false;
        if (!$moratorio) {
            foreach ($proyecciones as $proyeccion) {
                $mora += $proyeccion->valor_mora;
                $isCuota = true;
            }
        }

        $condicion = [
            ['credito_id', $credito->id],
            ['pagado', 0]
        ];

        $funcionAbonoCapital = EstadoFunciones::where('nombre_funcion','Abonar al capital')->first();
        $AbonoCapital = ParametrosEstadoFunciones::where([['empresa_id',$empresaId],['estado_funcion_id',$funcionAbonoCapital->id]])->first();

        if($AbonoCapital){
            $AbonoCapital = 1;
        }else{
            $AbonoCapital = 0;
        }

        if ($credito->credito_liquidez == 1) $AbonoCapital = 0;

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

        $isFinish = CreditoProyeccion::where($condicion)->count();

        $total_gastos_c = 0;
        $total_intereses_m = 0;
        // Activo para realizar abono a capital // se podra realizar abono a capital cualquier dia del mes
        $activoAbonoCapital = 1;
        $fechaAbonoCapital = 'Digite el valor';

        foreach($proyecciones as $proyeccion){
            if($proyeccion->pagado == 0){
                if($proyeccion->fecha->format('Y-m-d') < $hoy->format('Y-m-d')){
                    // si el cliente se encuentra en mora no se podra realizar abono a capital
                    $activoAbonoCapital = 0;
                    $fechaAbonoCapital = 'En mora';
                }

                $vIntMoratorios = round($proyeccion->intereses_moratorios);
                $vGastosCobranza = round($proyeccion->gastos_cobranza);

                $proyeccion->valor_mora_capital = $proyeccion->valor_mora
                    - $vGastosCobranza
                    - $vIntMoratorios;

                $total_intereses_m += $vIntMoratorios;
                $total_gastos_c += $vGastosCobranza;
            } else {
                $proyeccion->valor_mora_capital = 0;
            }
        }

        $table = $this->CalcularCapital($credito->id);
        $valorAFavor = 0;

        $valorACuotasAFavor = 0 ;
        foreach($abonos as $abono){
            //Restar interes mora
            $valorACuotas = $abono->valor;

            if($valorACuotas > 0 ){
                $valorACuotasAFavor = $valorACuotasAFavor + $valorACuotas;
            }else{
                $valorACuotasAFavor = $valorACuotasAFavor - $valorACuotas;
            }
        }

            // Resultado de la división (cociente)
        $cuotasSaldadas = intdiv($valorACuotasAFavor, $credito->val_cuotas);
        $valorAFavor = $valorACuotasAFavor % $credito->val_cuotas;

        $capitalEnDeuda = 0;

        foreach($table as $key => $tab){
            $capitalEnDeuda += $tab["capital"];

            if($cuotasSaldadas == 0){
                if($proyecciones[0]->pagado == 1){
                    $valorAFavor = 0;
                }
            }

            $capitalGC = 0;
            if($proyecciones[$key]->pagado == 0){
                $proyecciones[$key]->capital = round($tab["capital"],0);
                if($valorAFavor > 0){

                    if($tab["firmaElec"]>0){
                        $capitalGC = $tab["capital"] +  $tab["intereses"] + $tab["firmaElec"] - $valorAFavor;
                    }else{
                        $capitalGC = $tab["capital"] +  $tab["intereses"] - $valorAFavor;
                    }

                    $resultado = $credito->val_cuotas - $valorAFavor;

                    if($resultado < $proyecciones[$key]->capital){
                        $proyecciones[$key]->capital = $resultado;
                    }

                    $valorAFavor = 0;
                }else{
                    if($tab["firmaElec"]>0){
                        $capitalGC = $tab["capital"] +  $tab["intereses"] + $tab["firmaElec"];
                    }else{
                        $capitalGC = $tab["capital"] +  $tab["intereses"];
                    }
                }


                $proyecciones[$key]->capitalGC =  round($capitalGC,0);

            }
        }

        // total pendiente por pagar capital
        $capitalEnDeuda -= $abonos->sum('abono_capital');
        if (!$credito->aval_columnas) {
            $capitalEnDeuda -= ($abonos->sum('abono_aval') + $abonos->sum('abono_iva_aval'));
        }

        $saldoMora = 0;
        $saldo = 0;
        /**
         * @param array $proyecciones
         * @param int $modular
         * @param int $valorCuota
         * @param bool $soloMora
         * @param bool $estadoFunciones
         * @param bool $gastosCobranzaaAu
         * @param bool $intMoratoriosAu
        */
        $saldoMora = $this->pagoMinimo($credito->proyecciones, null, null, true, true, $GastosCobranzaAu, $InteresesMoratoriosAu);
        $valor_abonado = Abono::where('credito_id', $credito->id)->sum('valor');

        // Se valida si se ha realizado condonaciones al credito
        $valorCondonaciones = Condonacion::whereIn('abono_id', function ($query) use($credito) {
            $query->select('id')->from('abono')->where('credito_id', $credito->id);
        })->where('concepto_condonacion', 'credito')->sum('valor_condonado');

        // Se añade el valor de las condonaciones al total abonado
        $valor_abonado += ($valorCondonaciones ?? 0);

        //aqui
        $modular = $this->calculoValorAFavor($proyecciones[0]->valor_cuota, $valor_abonado, $proyecciones, $credito) ?? 0;
        $cuotas = $proyecciones->where('pagado', 1)->count();
        //$saldo = $credito->valor_credito - ($abonos->sum('valor') + ($valorCondonaciones ?? 0));

        $saldo = $credito->valor_credito;
        $num = $cuotas;
        foreach ($table as $tab) {
            if($num > 0){
                $saldo -= $tab["valCuota"];
            }
            $num--;
        }
        $saldo = $saldo - $modular;

        // Si el valor de la mora es mayor a 0, se asigna el valor de la mora al saldo (pueden existir diferencias de 1 o 2 pesos)
        if (max(0, $saldo) == 0 && max(0, $saldoMora) != 0) $saldo = $saldoMora;

        // $saldo -= ($valorCondonaciones ?? 0);
        // $saldoMora -= ($valorCondonaciones ?? 0);

        $datos = array(
            'user' => $user,
            'Proyecciones' => $proyecciones,
            'total_gastos_c' => round($total_gastos_c),
            'total_intereses_m' => round($total_intereses_m),
            'InteresesMoratoriosAu' => $InteresesMoratoriosAu,
            'GastosCobranzaAu' => $GastosCobranzaAu,
            'AbonoCapital' => $AbonoCapital,
            'InteresAnual' => $parametrosIntereses->interes_ea,
            'credito' => $credito,
            'abonos' => $abonos,
            'condonaciones' => $condonaciones,
            'valorHoy' => round($valorPendiente),
            'valorMora' => $mora,
            'isCuota' => $isCuota,
            'moratorio' => $moratorio,
            'terminado' => ($isFinish > 0) ? false : true,
            'valorPrimerCuota' => $valorPrimerCuota,
            'saldo' => max(0, $saldo),
            'saldoMora' => max(0, $saldoMora),
            'activoAbonoCapital' => $activoAbonoCapital,
            'fechaAbonoCapital' => $fechaAbonoCapital,
            'capitalEnDeuda' => $capitalEnDeuda,
            'tipoPago' => TipoPago::all(),
            'totalAbonado' => $abonos->sum('valor')
        );

        return response()->json([
            'datos' => $datos
        ]);
    }
}
