<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ColoresFuente;
use App\Models\Credito;
use App\Models\CreditoProyeccion;
use App\Models\Empresa;
use App\Models\EstadoFunciones;
use App\Models\ParametrosEstadoFunciones;
use App\Models\ParametrosInterese;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobileController extends Controller
{
    protected $plandepagosObtenerSoloPlan = false;

    function obtenerPlanDePagos ($creditoId, $abonos = null)
    {
        $this->plandepagosObtenerSoloPlan = true;
        return $this->detallePlanDePagos(
            Request::create("/creditos" . "/" . $creditoId . "/generar_plan", 'POST', ['id' => $creditoId, 'abonos' => $abonos])
        );
    }

    function detallePlanDePagos(Request $request)
    {

        $respuesta = $this->generarTablaPlanPagos($request['id'], $request['abonos']);
        // acá termina el nuevo metodo que calcula el plan de pagos.
        $tabla = $respuesta['tabla'];
        $totales = $respuesta['totales'];
        $cliente = $respuesta['cliente'];
        $credito = $respuesta['credito'];
        $empresa = $respuesta['empresa'];
        $parametros = $respuesta['parametros'];
        $firmaElecTotal = $respuesta['firmaElecTotal'];
        $firmaIvaPorcentaje = $respuesta['firmaIvaPorcentaje'];
        $firmaElecPorcentaje = $respuesta['firmaElecPorcentaje'];
        $avalPorcentaje = $respuesta['avalPorcentaje'];
        $avalIvaPorcentaje = $respuesta['avalIvaPorcentaje'];
        $avalTotalizado = $respuesta['avalTotalizado'];

        $hoy = Carbon::now();

        $planPagos = $tabla;
        $totales = $totales;
        $datosPersona = $cliente;
        $datosCredito = $credito;
        $datosEmpresa = $empresa;
        $parametrosIntereses = $parametros;

        $base = (1 + ($datosCredito->por_nominal));
        $operacion =  ($base ** 12) - 1;

        $proyeccion = CreditoProyeccion::where('credito_id', $datosCredito->id)->get();

        if ($this->plandepagosObtenerSoloPlan) {
            return $planPagos;
        }

        $tasaInteres = $datosCredito->por_nominal * 100;
        if ($parametros->redondeo_intereses == 0) {
            $tasaMaximaLegal = floor($operacion * 100 * 100) / 100;
        } else {
            $tasaMaximaLegal = round(floor($operacion * 100 * 100) / 100);
        }

        // fecha credito UTC -5
        $datosCredito->fecha_credito = Carbon::parse($datosCredito->created_at)->subHours(5)->format('Y-m-d H:i:s');
        //Consultar si esta empresa uya tiene una configuracion creada
        $coloresFuente = null;
        //Consultar si la funcion Modificar color y fuente esta antiva
        $funcionColores = EstadoFunciones::where('nombre_funcion','Modificar color y fuente')->first();
        $ColoresAu = ParametrosEstadoFunciones::where([['empresa_id',$datosEmpresa['id']],['estado_funcion_id',$funcionColores->id]])->first();

        if($ColoresAu) $coloresFuente = ColoresFuente::where('empresa_id', $empresa->id)->first();

        $pdf = \PDF::loadView('pdf.detalleplanpago', compact('planPagos', 'totales', 'datosPersona', 'datosCredito', 'coloresFuente' ,'datosEmpresa', 'parametrosIntereses', 'operacion', 'firmaElecTotal', 'firmaIvaPorcentaje', 'firmaElecPorcentaje', 'avalPorcentaje', 'avalIvaPorcentaje', 'avalTotalizado','proyeccion', 'tasaInteres', 'tasaMaximaLegal'));

        $hoy = Carbon::now();
        $fileName = $cliente->cedula . '/planpagos' . $cliente->cedula . '_' . $hoy . '.pdf';
        Storage::disk('s3')->put($fileName, $pdf->output());
        Storage::disk('s3')->url($fileName);

        $expiracion = Carbon::now()->addMinutes(30); // Establecer la expiración en 5 minutos
        $url = Storage::disk('s3')->temporaryUrl($fileName, $expiracion);

        $resultado = array(
            "link" => $url,
            "id" => $credito->consecutivo,
            "creditoId" => $credito->id
        );

        return response()->json([
            'resultado' => $resultado
        ]);
    }

    function generarTablaPlanPagos($id, $abonos = null)
    {
        $condictions1 = [
            ['id', $id],
        ];
        $credito = Credito::where($condictions1)->first();

        $n = $credito->num_cuotas;
        $p = $credito->valor_compra;

        $empresa = Empresa::where('id', $credito->empresa_id)->first();

        // lineas de credito
        $vEmpresaId = $empresa->id;
        // Validar si la empresa es un aliado o una sede
        if ($empresa->aliado || $empresa->sede) {
            $empresaPrincipal = Empresa::where('id', $empresa->aliado ?? $empresa->sede)->first();
            if ($empresaPrincipal) $vEmpresaId = $empresaPrincipal->id;
        }

        $parametros = ParametrosInterese::withTrashed()
            ->where('empresa_id', $vEmpresaId)
            ->where('lineas_credito_id', $credito->lineas_credito_id)
            ->first();

        $cliente = Cliente::where('id', $credito->client_id)->first();

        $hoy = Carbon::now();

        $periodicidad = $credito->periocidad;
        // acá va el tema de los vencimientos
        if ($periodicidad != 1) $n = $n * 2;
        $fechas = $this->calculoPlanPagos(null, $periodicidad, $n);

        // Validacion de si el aval se debe cobrar totalizado o mes a mes
        $avalTotalizado = $credito->aval_columnas == 0 ? true : false;
        $restarAval = $credito->restar_aval == 1 ? true : false;
        $avalTotalizadoTemp = $avalTotalizado;
        if ($abonos) $avalTotalizado = false; // se usa la flag $abonos para discriminar el aval en columnas aun si es totalizado por defecto

        // Aval nominal, porcentual e iva
        $aval = 0;
        $avalPorcentaje = 0;
        $baseAval = $abonos ? $credito->valor_base : $p;
        if (!empty($credito->aval_porcentaje) && is_numeric($credito->aval_porcentaje)) {
            $avalPorcentaje = $credito->aval_porcentaje;
            if ($avalTotalizado) {
                $vSinAval = $baseAval - ($baseAval * ($credito->aval_porcentaje / 100));
                $aval = ($vSinAval * ($credito->aval_porcentaje / 100));
            } else {
                $aval = ($baseAval * ($credito->aval_porcentaje / 100)) / $n;
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

        // se restaura el valor de $avalTotalizado
        if ($abonos) $avalTotalizado = $avalTotalizadoTemp;

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

                    // Fecha de pago formateada
                    $fechaPago = Carbon::parse($proyeccion->fecha)->startOfDay();

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

            // flag utilizado para calcular el valor de capital sin aval en el informe abonos
            if ($abonos && $avalTotalizado) $capital -= round($aval ?? 0) + round($avalIva ?? 0);

            // paso 5: calcúlo de cuota:
            if (!is_null($proyeccion->valor_cuota)) {
                $valCuota = $credito->proyecciones[$j]->valor_cuota;
            } else {
                if ($avalTotalizado) {
                    $valCuota = $firmaElec + $firmaIva + $otrosValor + ($enGracia ? ($capital + $interesesGracia) : $valCuotaFija);
                } else {
                    $valCuota = $firmaElec + $firmaIva + (!$restarAval ? $aval + $avalIva : 0) + $otrosValor + ($enGracia ? ($capital + $interesesGracia) : $valCuotaFija);
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
                'aval' => ($abonos && $avalTotalizado) ? $aval : ($avalTotalizado ? 0 : $aval),
                'avalIva' => ($abonos && $avalTotalizado) ? $avalIva : ($avalTotalizado ? 0 : $avalIva),
                'otros' => $otrosValor,
                'valCuotaSinRed' => $valCuotaFija,
                'valCuota' => $valCuota
            );
        }

        $totales = array(
            'capital' => 0,
            'intereses' => 0,
            'otroIntereses' => 0,
            'firmaElec' => 0,
            'firmaIva' => 0,
            'aval' => 0,
            'avalIva' => 0,
            'otros' => 0,
            'valCuotaSinRed' => 0,
            'valCredito' => 0,
            'valCuota' => $valCuota
        );

        $i = -1;
        foreach ($tabla as $t) {
            $i++;

            $totales['capital'] += round($t['capital']);
            $totales['intereses'] += round($enGracia ? $t['interesesGracia'] : $t['intereses']);
            $totales['otroIntereses'] += round($t['otroIntereses']);
            $totales['firmaElec'] += round($t['firmaElec']);
            $totales['firmaIva'] += round($t['firmaIva']);
            $totales['aval'] += round($t['aval']);
            $totales['avalIva'] += round($t['avalIva']);
            $totales['otros'] += round($t['otros']);
            $totales['valCuotaSinRed'] += round($t['valCuotaSinRed']);
            $totales['valCredito'] += round($t['valCuota']);

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

        $i = -1;
        foreach ($totales as $t) {
            $i++;
            // $totales['capital'] = round($totales['capital']);
            $totales['capital'] = $credito->valor_compra;
            $totales['intereses'] = round($totales['intereses']);
            $totales['otroIntereses'] = round($totales['otroIntereses']);
            $totales['firmaElec'] = round($totales['firmaElec']);
            $totales['firmaIva'] = round($totales['firmaIva']);
            $totales['aval'] = round($avalTotalizado ? $aval : $totales['aval']);
            $totales['avalIva'] = round($avalTotalizado ? $avalIva : $totales['avalIva']);
            $totales['otros'] = round($totales['otros']);
            $totales['valCuotaSinRed'] = round($totales['valCuotaSinRed']);
            // $totales['valCredito'] = round($totales['valCredito']);
            $totales['valCredito'] = $credito->valor_credito;
            $totales['valCuota'] = round($totales['valCuota']);
            # code...
        }

        $resultado = array(
            'tabla' => $tabla,
            'totales' => $totales,
            'fechas' => $fechas,
            'credito' => $credito,
            'parametros' => $parametros,
            'empresa' => $empresa,
            'cliente' => $cliente,
            'firmaElecTotal' => $firmaElecTotal,
            'firmaIvaPorcentaje' => $firmaIvaPorcentaje,
            'firmaElecPorcentaje' => $firmaElecPorcentaje,
            'avalPorcentaje' => $avalPorcentaje,
            'avalIvaPorcentaje' => $avalIvaPorcentaje,
            'avalTotalizado' => $avalTotalizado ? 0 : 1
        );

        return $resultado;
    }

    /**
     * Calculo de fechas pago crédito.
     *
     * @param Carbon $startProyeccion fecha inicial del plan de pagos
     * @param int $periodicidad 1 = mensual, otro = cada 15 días
     * @param int $n número de cuotas
     * @return array fechas de pago en formato Y-m-d
     */
    public function calculoPlanPagos($startProyeccion, $periodicidad, $n)
    {
        $hoy = Carbon::now();
        $fechas = [];

        $fechaBase = isset($startProyeccion)
            ? $startProyeccion->copy()
            : $hoy->copy();

        if ($periodicidad == 1) {
            for ($j = 0; $j < $n; $j++) {
                // sumar n cantidad de meses a la fecha original sin debordamiento de fecha
                $nuevaFecha = $fechaBase->copy()->addMonthsNoOverflow($j);
                $fechas[] = $nuevaFecha->format('Y-m-d');
            }
        } else {
            //quince dias
            for ($j = 0; $j < $n; $j++) {
                $diasASumar = 15 * $j;
                $nuevaFecha = $fechaBase->copy()->addDays($diasASumar);
                $fechas[] = $nuevaFecha->format('Y-m-d');
            }
        }

        return $fechas ?? [];
    }

    function calcularAnual($efectivoNominal)
    {
        $base = $efectivoNominal + 1;
        $porcentaje = (pow($base, 12) - 1) * 100;
        return $porcentaje;
    }
}
