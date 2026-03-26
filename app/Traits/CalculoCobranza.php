<?php

namespace App\Traits;

use DateTime;
use Carbon\Carbon;
use App\Models\Credito;
use App\Models\TasaUsura;
use App\Models\Condonacion;
use App\Models\TablasCobranza;
use App\Http\Controllers\Mobile\MobileController;

trait CalculoCobranza
{
    public function calculoCobranzaIntMora($vCreditos)
    {
        // Buscar creditos sin fecha de cierre
        $creditos = Credito::with(['proyecciones', 'abonos', 'cliente:id,fecha_fin_acuerdo_pago'])
            ->select('id', 'val_cuotas', 'empresa_id', 'client_id', 'fecha_cierre')
            ->whereNull('fecha_cierre')
            ->when(!empty($vCreditos), function ($query) use ($vCreditos) {
                return $query->whereIn('id', $vCreditos);
            })
            ->get();

        $hoy = now();

        foreach ($creditos as $credito) {
            // validar si el cliente tiene un acuerdo de pago vigente // no tarifica int mora ni gastos de cobranza hasta la fecha fin del acuerdo
            if ($credito->cliente !== null && $credito->cliente->fecha_fin_acuerdo_pago !== null){
                $fechaFinAcuerdoPago = Carbon::parse($credito->cliente->fecha_fin_acuerdo_pago)->endOfDay();
                if ($hoy->lessThanOrEqualTo($fechaFinAcuerdoPago)) continue;
            }

            // Buscar las proyecciones del credito
            $proyecciones = $credito->proyecciones;

            if(count($proyecciones) > 0 &&  $credito->cliente !== null){
                // Se valida si se ha realizado condonaciones al credito
                $valorCondonaciones = Condonacion::whereIn('abono_id', function ($query) use($credito) {
                    $query->select('id')->from('abono')->where('credito_id', $credito->id);
                })->where('concepto_condonacion', 'credito')->sum('valor_condonado');

                // Informacion de los abonos realizados al credito
                $totalAbonado = $credito->abonos->sum('valor');

                // Se añade el valor de las condonaciones al total abonado
                $totalAbonado += ($valorCondonaciones ?? 0);

                // Validar que al menos una de las proyecciones tenga pagado en 0
                $pagadoEn0 = $proyecciones->contains('pagado', 0);
                // Valor total abonado al credito
                $valorAFavor = 0; // Valor a favor del cliente sobrante de los abonos anteriores
                $abonoFavor = 0;

                foreach($proyecciones as $proyeccion){
                    $valorCuota = is_null($proyeccion->valor_cuota) ? $credito->val_cuotas : $proyeccion->valor_cuota;
                    $totalAbonado -= $valorCuota;

                    /**
                     * Si el total abonado es negativo y el valor de la cuota  mas el total abonado es mayor a 0
                     * quiere decir que para dicha cuota existe un valor a favor que se pago de mas en ultimo abono
                    */
                    if (($totalAbonado < 0) && (($valorCuota + $totalAbonado) >= 0)) {
                        $valorAFavor = $valorCuota + $totalAbonado;
                        $abonoFavor = $valorCuota + $totalAbonado;
                    }
                }

                // Se valida que al menos una de las proyecciones no ha sido pagada
                if($pagadoEn0){
                    // Calcular la cantidad de cuotas del credito que ya han sido pagadas
                    $cuotasSaldadas = $proyecciones->where('pagado', 1)->count();

                    //Calcular capital del credito
                    $tabla = (new MobileController)->obtenerPlanDePagos($credito->id);
                    array_shift($tabla); //Eliminar el primer arreglo de $tabla el cual contiene valores en 0

                    foreach($tabla as $key => $tab){
                        if($cuotasSaldadas == 0){
                            if(isset($proyecciones[0])){
                                if($proyecciones[0]->pagado == 1){
                                    $valorAFavor = 0;
                                }
                            }
                        }

                        if($proyecciones[$key]->pagado == 0){
                            $proyecciones[$key]->capital = round($tab["capital"], 0); // Valor capital obtenido de la tabla

                            if($valorAFavor > 0){
                                $valorCuota = is_null($proyeccion->valor_cuota) ? $credito->val_cuotas : $proyeccion->valor_cuota;
                                $resultado = $valorCuota - $valorAFavor;

                                if($resultado < $proyecciones[$key]->capital) {
                                    $proyecciones[$key]->capital = $resultado;
                                }

                                $valorAFavor = 0;
                            }
                        }else{
                            unset($proyecciones[$key]);
                        }
                    }

                    /*****************************************************************************************/
                    /** Del codigo anterior se obtienen unicamente las proyecciones que no han sido pagadas **/
                    /*****************************************************************************************/

                    foreach ($proyecciones as $proyeccion) {
                        $proyeccionFecha = Carbon::parse($proyeccion->fecha)->startOfDay();

                        if ($hoy->gt($proyeccionFecha)) {
                            $diferencia = (int) $hoy->diffInDays($proyeccionFecha, true);

                            if ($diferencia > 0) {
                                $proyeccion->diasMora = $diferencia; // Dias de mora de la cuota proyectada
                                $datosCalculo = $this->calcularDiasPorMes($proyeccion->diasMora);
                                $totalInteresM = 0;

                                foreach ($datosCalculo as $datoCalculo) {
                                    $InteresM = $proyeccion->capital * ((($datoCalculo['tasaUsuraMes'] / 100) / 30) * $datoCalculo['dias']);
                                    $totalInteresM += $InteresM;
                                }

                                $proyeccion->intereses_moratorios = max(0, $totalInteresM);

                                // Gastos de cobranza 'PROPIA'
                                $porcentaje = $this->obtenerPorcentaje($proyeccion->diasMora, $credito->empresa_id , 'PROPIA');
                                $valorCuota = is_null($proyeccion->valor_cuota) ? $credito->val_cuotas : $proyeccion->valor_cuota;
                                $valorMora = $valorCuota - $abonoFavor;
                                $abonoFavor = 0;

                                $gastosCobranza = ($porcentaje / 100) * ($valorMora);
                                $proyeccion->gastos_cobranza = max(0, $gastosCobranza);

                                // Gastos de cobranza 'CASA COBRANZA
                                $porcentaje = $this->obtenerPorcentaje($proyeccion->diasMora, $credito->empresa_id, 'CASA COBRANZA');
                                $casa_gas_cobranza = ($porcentaje / 100) * ($valorMora);
                                $proyeccion->casa_gas_cobranza = max(0, $casa_gas_cobranza);

                                //Actualizar el valor de la mora
                                $proyeccion->valor_mora = $valorMora + $proyeccion->gastos_cobranza + $proyeccion->intereses_moratorios;

                                //Eliminar de la proyeccion a capital y capitalGC ya que estos campos no son de DB
                                unset($proyeccion->capital);
                                unset($proyeccion->capitalGC);

                                $proyeccion->save();
                            }
                        }
                    }
                }else{
                    $credito->fecha_cierre = $hoy;
                    $credito->update();
                }
            }else{
                $credito->fecha_cierre = $hoy;
                $credito->update();
            }
        }
    }

    private function calcularDiasPorMes($dias) {
        $resultado = [];
        $fechaActual = new DateTime(); // Fecha actual

        while ($dias > 0) {
            $mesActual = (int)$fechaActual->format('m'); // Mes actual (1-12)
            $añoActual = (int)$fechaActual->format('Y'); // Año actual

            // Obtener la cantidad de días en el mes actual
            $diasEnMes = (int)$fechaActual->format('t');

            // Determinar cuántos días del mes actual consumir
            $diasRestantesEnMes = (int)$fechaActual->format('j'); // Día del mes actual
            $diasConsumidos = min($dias, $diasRestantesEnMes);

            $fecha = $añoActual.'-'.$mesActual.'-01';

            $carbonFecha = Carbon::parse($fecha);

            $tasaUsura  = TasaUsura::select('interes')->whereYear('fecha', $carbonFecha->year)
            ->whereMonth('fecha', $carbonFecha->month)
            ->first();

            $tasaUsuraMes = round((pow((1 + $tasaUsura->interes / 100), (1 / 12)) - 1) * 100,2);

            // Agregar al resultado
            $resultado[] = [
                'fecha' => $fecha,
                'tasaUsuraMes' => $tasaUsuraMes,
                'dias' => $diasConsumidos
            ];

            // Restar los días consumidos
            $dias -= $diasConsumidos;

            // Ajustar la fecha al último día del mes anterior
            $fechaActual->modify('first day of last month');
            $fechaActual->modify('last day of this month');
        }

        return $resultado;
    }

    private function obtenerPorcentaje($numero,$empresa_id , $tipo) {
        if($numero > 0){
            // Primero intentamos buscar los registros correspondientes a la empresa
            $valor = TablasCobranza::where([
                ['empresa_id', $empresa_id],
                ['tipo', $tipo],
                ['dias_limit_inf', '<=', $numero],
                ['dias_limit_sup', '>=', $numero]
            ])->first();

            // Si no se encuentra ningún registro, buscamos en las tablas por defecto
            if (!$valor) {
                $valor = TablasCobranza::where([
                    ['empresa_id', null],
                    ['tipo', $tipo],
                    ['dias_limit_inf', '<=', $numero],
                    ['dias_limit_sup', '>=', $numero]
                ])->first();
            }
            return $valor ? $valor->porcentaje : 0;
        }else{
            return 0;
        }
    }
}
