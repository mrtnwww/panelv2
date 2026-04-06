<?php

namespace App\Http\Controllers\Creditos;

use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\MobileController;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Condonacion;
use App\Models\ConvenioLibranza;
use App\Models\Credito;
use App\Models\CreditoProyeccion;
use App\Models\Empresa;
use App\Models\EstadoFunciones;
use App\Models\LineasCredito;
use App\Models\Notification;
use App\Models\NuevaAutorizacionConsulta;
use App\Models\ParametrosEstadoFunciones;
use App\Models\ParametrosInterese;
use App\Models\Persona;
use App\Models\ReporteCentralesHistorial;
use App\Models\TipoPago;
use App\Models\Usuario;
use App\Models\UsuarioTipoUsuario;
use App\Traits\CalculoCobranza;
use App\Traits\CalculoCobranzaTemp;
use App\Traits\CalculoPagoMinimo;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditoController extends Controller
{
    use CalculoCobranza;
    use CalculoPagoMinimo;
    use CalculoCobranzaTemp;

    public function listCredits(Request $request)
    {
        $usuario = auth()->user();

        $usuarioId = $usuario->id;
        $empresaId = $usuario->empresa_id;

        $hoy = Carbon::now();

        $isAdmin = UsuarioTipoUsuario::where('id_usuario', $usuarioId)
            ->where('id_tipo_usuario', 2)
            ->exists();

        // Número de créditos por página
        $perPage = $request->per_page;

        // Formulario desde donde se genera la busqueda
        $form = $request->input('form', null);

        // Filtros
        $conditions = $request->input('conditions', []);

        // Término de búsqueda
        $searchTerm = $request->input('search', '');

        // Nit de la empresa del usuario
        $empresa = Empresa::find($empresaId);

        $isAliado = ($empresa->sede || $empresa->aliado) ? true : false;
        $empresaNit = $empresa->nit;

        // Verificar si el usuario tiene rol igual a 2
        $anular = $isAdmin;

        // Se le restringe el acceso de anulacion de creditos al ing. Victor
        if (in_array($usuarioId, [5859, 6163, 6309])) $anular = false;

        // validar la funcion de generar extracto
        /** Enviar pagare de credito al correo del cliente */
        $generarExtracto = ParametrosEstadoFunciones::where('empresa_id', $empresaId)
            ->whereHas('estado_funcion', function($query) {
                $query->where('nombre_funcion', 'Generar extracto');
            })
            ->exists();

        // Obtener créditos de aliados
        $listaSedesAliados = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id');

        // $creditosQuery = Credito::withTrashed() // Se deja comentado ya que antes de la optimizacion no se estaban consultando los creditos eliminados (Pendiente de comentarlo con el Ing Andres para confirmar si tambien debe traer estos registros)
        $creditosQuery = Credito::where(function ($query) use ($empresaId, $listaSedesAliados, $conditions) {
                if (!array_key_exists('soloAliados', $conditions) || !$conditions['soloAliados']) {
                    $query->where('empresa_id', $empresaId);
                }
                $query->orWhereIn('empresa_id', $listaSedesAliados);
            })
                ->with([
                    'proyecciones',
                    'user.persona',
                    'cliente:id,nombre',
                    'empresa:id,razon_social,nit',
                    'lineasCredito:id,tipo_credito',
                    'abonos:credito_id,abono_gas_cobranza,abono_int_mora'
                ])
                ->applyConditions($conditions, $form)
                ->applySearch($searchTerm, $form)
                ->orderBy('id', 'desc');

        // Se utiliza para obtener los totales y subtotales de los conceptos de int moratorios y gastos de cobranza
        $adicionales = clone $creditosQuery;
        $adicionales->setEagerLoads([]); // limpiar relaciones
        // Total y subtotal de gastos de cobranza e intereses moratorios
        /* TOTALES */
        $totalIntMora = 0;
        $totalIvaAval = 0;
        $totalGasCobranza = 0;
        $total_cxc_aliado = 0;
        $total_pendiente_mora = 0;
        $total_credito_intereses = 0;
        /* SUBTOTALES */
        $valorSubTotalIntMora = 0;
        $valorSubTotalGasCobranza = 0;
        $subtotal_pendiente_mora = 0;
        $subtotal_credito_intereses = 0;

        // fecha actual
        $now = now();

        // obtener proyecciones de los creditos en consulta
        $proyeccionesCreditos = CreditoProyeccion::whereIn('credito_id', $adicionales->pluck('id'))
            ->select('id','credito_id','pagado','fecha','intereses_moratorios','gastos_cobranza', 'valor_mora', 'valor_cuota')
            ->get()
            ->groupBy('credito_id');

        // actualizacion masiva de intereses
        $updatesIntereses = [];

        $adicionales->select([
            'id', 'valor_intereses', 'deleted_at', 'aval_value', 'aval_iva', 'valor_base', 'valor_cxc', 'valor_compra', 'num_cuotas', 'periocidad', 'por_anual', 'por_nominal', 'otro_por_ea', 'created_at'
        ])->chunk(500, function($creditos) use ( &$totalIntMora, &$totalGasCobranza, &$valorSubTotalIntMora, &$valorSubTotalGasCobranza, &$totalIvaAval, &$total_cxc_aliado, &$total_credito_intereses, &$subtotal_credito_intereses, &$total_pendiente_mora, &$subtotal_pendiente_mora, &$updatesIntereses, $now, $proyeccionesCreditos) {
            foreach ($creditos as $credito) {
                $calculoIntereses = 0;
                $proyecciones = $proyeccionesCreditos[$credito->id] ?? collect();
                $credito->proyecciones = $proyecciones;

                if (is_null($credito->valor_intereses)) {
                    $calculoIntereses = $this->calcularIntereses($credito);
                    if ($calculoIntereses) {
                        $updatesIntereses[] = [
                            'id' => $credito->id,
                            'valor_intereses' => $calculoIntereses,
                        ];

                        $total_credito_intereses += $calculoIntereses;
                    }
                } else {
                    $calculoIntereses += $credito->valor_intereses ?? 0;
                    $total_credito_intereses += $calculoIntereses;
                }

                // validar si el credito se ha eliminado o no
                $isActive = is_null($credito->deleted_at);

                if ($isActive) $subtotal_credito_intereses += $calculoIntereses;

                // iva del aval
                if (!empty($credito->aval_value)) $totalIvaAval += round(($credito->aval_value * ($credito->aval_iva ?? 0)) / 100);

                // cuotas pendientes
                $proyeccionesNoPagadas = $proyecciones->where('pagado', 0)
                    ->where('fecha', '<', $now)
                    ->values();

                if ($proyeccionesNoPagadas->isNotEmpty()) {
                    $sumIntMora     = $proyeccionesNoPagadas->sum(fn($p) => round($p->intereses_moratorios ?? 0));
                    $sumGasCobranza = $proyeccionesNoPagadas->sum(fn($p) => round($p->gastos_cobranza ?? 0));

                    $totalIntMora += $sumIntMora;
                    $totalGasCobranza += $sumGasCobranza;

                    if ($isActive) {
                        $valorSubTotalIntMora     += $sumIntMora;
                        $valorSubTotalGasCobranza += $sumGasCobranza;
                    }

                        // Calcular mora total pendiente
                    $calculoPendienteMora = $this->pagoMinimo(
                        $proyecciones,
                        null, null,
                        true, true, 1, 1
                    ) ?? 0;

                    $total_pendiente_mora += $calculoPendienteMora;
                    if ($isActive) $subtotal_pendiente_mora += $calculoPendienteMora;
                }

                // Total cuenta por cobrar aliado
                $total_cxc_aliado += ($credito->valor_base ?? 0) - ($credito->valor_cxc ?? 0);
            }
        });

        // actualizar intereses de manera masiva
        if (!empty($updatesIntereses)) {
            $cases = [];
            $ids = [];

            foreach ($updatesIntereses as $update) {
                $id = $update['id'];
                $valor = $update['valor_intereses'];
                $cases[] = "WHEN {$id} THEN {$valor}";
                $ids[] = $id;
            }

            $idsStr = implode(',', $ids);
            $casesStr = implode(' ', $cases);

            DB::statement("UPDATE credito SET valor_intereses = CASE id {$casesStr} END WHERE id IN ({$idsStr})");
        }

        // Se utiliza para obtener los totales y subtotales de todos los créditos
        $totales = clone $creditosQuery;
        // Valores totales de crédito, contado y comisiones de las cajeras
        $valorComisiones = $totales->sum('user_comision');
        $valorCredito = $totales->sum('valor_credito');
        $totalAval = $totales->sum('aval_value');

        // Total cuenta por cobrar
        $valorCXC = $totales->sum('valor_cxc');

        // Total de abonos realizados
        $totalAbonado = Abono::whereIn('credito_id', $totales->pluck('id'))->sum('valor');

        // Valor total base (Si el credito tiene aval, el valor base sera el valor de la compra - aval - iva aval)
        $totalBase = $totales->sum('valor_base') ?? 0;
        $totalCompra = $totales->sum('valor_compra') ?? 0;

        // Valores subtotales de crédito y contado
        $valorSubTotalCredito = $totales->whereNull('deleted_at')->sum('valor_credito');
        $valorSubTotalAval = $totales->whereNull('deleted_at')->sum('aval_value');

        // Subtotal cuenta por cobrar
        $valorSubTotalCXC = $totales->whereNull('deleted_at')->sum('valor_cxc');

        // Subtotal de abonos realizados
        $totalSubAbonado = Abono::whereIn('credito_id', $totales->whereNull('deleted_at')->pluck('id'))->sum('valor');

        // Todos los id de los creditos consultados
        $creditosIds = $creditosQuery->pluck('id');

        // Paginación
        $creditos = $creditosQuery->paginate($perPage);

        // Obtener abonos y proyecciones de cada crédito
        $abonosPorCredito = Abono::whereIn('credito_id', $creditos->pluck('id'))->get()->groupBy('credito_id');
        $proyeccionesPorCredito = CreditoProyeccion::whereIn('credito_id', $creditos->pluck('id'))->get()->groupBy('credito_id');

        // Transformar los créditos
        $creditos->getCollection()->transform(function ($credito) use ($hoy, $anular, $abonosPorCredito, $proyeccionesPorCredito, $isAdmin, $empresaId , $generarExtracto) {

            $condicional = [['credito_id', $credito->id], ['pagado', 0]];
            $isMora = false;

            $isFinalizado = CreditoProyeccion::where($condicional)->count();
            $proyeccionLast = $proyeccionesPorCredito->get($credito->id)->last();

            // Verificamos las proyecciones para determinar isMora
            foreach ($credito->proyecciones->where('pagado', 0) as $proyeccion) {
                $datetime1 = new DateTime($hoy);
                $datetime2 = new DateTime($proyeccion->fecha);
                $interval = $datetime1->diff($datetime2);
                $omg = $interval->format('%R%a');
                if (0 > $omg) $isMora = true;
            }

            $intMora = 0;
            $gasCobranza = 0;
            $credito->proyecciones->each(function($proyeccion) use (&$intMora, &$gasCobranza) {
                if ($proyeccion->pagado == 0) {
                    $intMora += round($proyeccion->intereses_moratorios ?? 0);
                    $gasCobranza += round($proyeccion->gastos_cobranza ?? 0);
                }
            });

            $valorAval = $credito->aval_value ?? 0;
            $valorAvalIva = ($valorAval * ($credito->aval_iva ?? 0)) / 100;

            // Valor CXC aliado
            $valor_cxc_aliado = ($credito->valor_base ?? 0) - ($credito->valor_cxc ?? 0);

            // Saldo del crédito
            $saldo = 0;

            $empresaId = $empresaId;
            $InteresesMoratoriosAu = $this->obtenerEstadoFuncion('Intereses moratorios Automáticos', $empresaId);
            $GastosCobranzaAu = $this->obtenerEstadoFuncion('Gastos de cobranza Automáticos', $empresaId);

            /**
             * @param array $proyecciones
             * @param int $modular
             * @param int $valorCuota
             * @param bool $soloMora
             * @param bool $estadoFunciones
             * @param bool $gastosCobranzaaAu
             * @param bool $intMoratoriosAu
            */
            $table = $this->CalcularCapital($credito->id);
            $abono = $abonosPorCredito->get($credito->id, collect())->sum('valor');

            //aqui
            $modular = $this->calculoValorAFavor($credito->proyecciones[0]->valor_cuota, $abono, $credito->proyecciones, $credito) ?? 0;
            $cuotas = $credito->proyecciones->where('pagado', 1)->count();
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

            $valorMora = $this->pagoMinimo($credito->proyecciones, null, null, true, true, $GastosCobranzaAu, $InteresesMoratoriosAu) ?? 0;

            // Si el valor de la mora es mayor a 0, se asigna el valor de la mora al saldo (pueden existir diferencias de 1 o 2 pesos)
            if (max(0, $saldo) == 0 && max(0, $valorMora) != 0) $saldo = $valorMora;

            // Se valida si se ha realizado condonaciones al credito
            $valorCondonaciones = Condonacion::whereIn('abono_id', function ($query) use($credito) {
                $query->select('id')->from('abono')->where('credito_id', $credito->id);
            })->where('concepto_condonacion', 'credito')->sum('valor_condonado');

            $saldo -= ($valorCondonaciones ?? 0);

            if (is_null($credito->valor_intereses)) {
                $intereses = $this->calcularIntereses($credito);
            } else {
                $intereses = $credito->valor_intereses;
            }

            // Valor base (Si el credito tiene aval, el valor base sera el valor de la compra - aval - iva aval)
            $valorBase = !empty($credito->valor_base)
                ? $credito->valor_base
                : $credito->valor_compra;
            $valorCompra = $credito->valor_compra ?? 0;

            return [
                'id' => $credito->id,
                'consecutivo' => $credito->empresa->id == 107 ? $credito->consecutivo : $credito->id,
                'fecha_credito' => Carbon::parse($credito->created_at)->subHours(5)->format('Y-m-d'),
                'vencimiento' => $proyeccionLast ? date("Y-m-d", strtotime($proyeccionLast->fecha->format('Y-m-d'))) : null,
                'plazo' => $credito->periocidad == 1 ? 'Mensual' : 'Quincenal',
                'valor_contado' => $valorBase,
                'valor_compra' => $valorCompra,
                'valor_credito' => $credito->valor_credito,
                'num_cuotas' => $credito->num_cuotas,
                'val_cuotas' => $credito->val_cuotas,
                'listaAbono' => [],
                'enmora' => $isMora,
                'total_abonado' => $abono,
                'saldo' => max(0, $saldo),
                'nombre' => $credito->cliente->nombre ?? '',
                'client' => $credito->cliente->id ?? '',
                'anular' => $anular,
                'anulado' => (bool)$credito->deleted_at,
                'finalizado' => $isFinalizado > 0 ? 0 : 1,
                'isDeletedAt' => (bool)$credito->deleted_at,
                'empresa' => $credito->empresa->razon_social ?? '',
                'empresa_nit' => $credito->empresa->nit ?? null,
                'placa' => $credito->placa,
                'producto' => $credito->producto,
                'valor_cxc' => $credito->valor_cxc ?? 0,
                'referencia' => $credito->referencia,
                'observacion' => $credito->observacion,
                'abono_int_mora' => $intMora,
                'abono_gas_cobranza' => $gasCobranza,
                'usuario' => isset($credito->user->persona->nombre) ? strtoupper($credito->user->persona->nombre) : '',
                'comision_cajera' => $credito->user_comision ?? 0,
                'isAdmin' => $isAdmin,
                'valor_aval' => $valorAval,
                'valor_iva_aval' => $valorAvalIva,
                'valor_cxc_aliado' => max(0, $valor_cxc_aliado),
                'intereses' => $intereses,
                'valor_mora' => $valorMora,
                'generarExtracto' => $generarExtracto,
                'destino' => $credito->lineasCredito ? $credito->lineasCredito->tipo_credito : ''
            ];
        });

        return response()->json([
            'allCreditsIds' => $creditosIds,
            'empresaNit' => $empresaNit,
            'isAliado' => $isAliado,
            'creditos' => $creditos,
            'totales' => [
                'saldo' => $valorCredito - $totalAbonado ?? 0,
                'valor_contado' => $totalBase ?? 0,
                'valor_compra' => $totalCompra ?? 0,
                'valor_credito' => $valorCredito ?? 0,
                'total_abonado' => $totalAbonado ?? 0,
                'valor_cxc' => $valorCXC ?? 0,
                'total_abono_int_mora' => $totalIntMora ?? 0,
                'total_abono_gas_cobranza' => $totalGasCobranza ?? 0,
                'valor_comisiones' => $valorComisiones ?? 0,
                'total_aval' => $totalAval ?? 0,
                'total_iva_aval' => $totalIvaAval ?? 0,
                'total_cxc_aliado' => $total_cxc_aliado,
                'total_credito_intereses' => $total_credito_intereses ?? 0,
                'total_credito_mora' => $total_pendiente_mora ?? 0
            ],
            'subtotales' => [
                'saldo' => $valorSubTotalCredito - $totalSubAbonado ?? 0,
                'valor_contado' => $totalBase ?? 0,
                'valor_compra' => $totalCompra ?? 0,
                'valor_credito' => $valorSubTotalCredito ?? 0,
                'total_abonado' => $totalSubAbonado ?? 0,
                'valor_cxc' => $valorSubTotalCXC ?? 0,
                'total_abono_int_mora' => $valorSubTotalIntMora ?? 0,
                'total_abono_gas_cobranza' => $valorSubTotalGasCobranza ?? 0,
                'total_aval' => $valorSubTotalAval ?? 0,
                'subtotal_credito_intereses' => $subtotal_credito_intereses ?? 0,
                'subtotal_credito_mora' => $subtotal_pendiente_mora ?? 0
            ]
        ]);
    }

    public function creditDetails(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

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

    public function clienteCreditData(Request $request) {
        $empresaId = auth()->user()->empresa_id;

        $condiction = [
            ['empresa_id', $empresaId],
            ['id', $request['id']]
        ];

        // Busqueda del cliente a partir del id de la empresa del usuario logueado y el id del usuario
        $cliente = Cliente::where($condiction)->first();

        // Si el cliente no existe
        if (!$cliente) {
            // Se consultan los aliados y sedes de la empresa
            $listaSedesAliados = Empresa::where('aliado', $empresaId)->orWhere('sede', $empresaId)->get();

            // Se iteran los aliados y sedes para buscar el cliente
            foreach ($listaSedesAliados as $sa) {
                $condiction1 = [
                    ['empresa_id', $sa->id],
                    ['id', $request['id']]
                ];
                $cliente = Cliente::where($condiction1)->first();
                if ($cliente) {
                    break;
                }
            }
        }

        // Se consultan los créditos del cliente
        $creditos =  Credito::where('client_id', $cliente->id)
            ->select('id', 'valor_compra')
            ->whereNull('fecha_cierre')
            ->with(['proyecciones' => function ($query) {
                $query->where('pagado', 0)->orderBy('fecha');
            }])->get();

        // 1. validar si el cliente puede tener mas de un credito vigente de forma simultanea
        if ($creditos->count() > 0) {
            $creditosSimultaneos = $this->validarCreditosSimultaneos($request['id']);
            if (!$creditosSimultaneos['continuar']) return $creditosSimultaneos['response'];
        }

        // 2. se valida si aplica reconsulta nuevamente en centrales de riesgo
        $resultadoValidacion = $this->validarConsultaCentrales($request['id']);
        if (!$resultadoValidacion['continuar']) return $resultadoValidacion['response'];

        /**
         * 3. validaciones adicionales aplican para todos los clientes
         * * referencias
         * * autorizacion consulta
         * * consulta en centrales
        */
        $validacionesCliente = $this->validacionesCliente($cliente);
        if (!$validacionesCliente['continuar']) return $validacionesCliente['response'];

        //si hay más de un crédito , debemos verificar si tiene cupo.
        $tieneCreditos = false;

        $cupo = $cliente->cupo ?? 0;
        $enMora = false;

        // Se iteran los creditos del cliente
        foreach ($creditos as $credito) {
            // Se calcula el cupo del cliente descontando el valor de los creditos iterados
            $cupo -= $credito->valor_compra;

            $abonosCliente = Abono::select('id', 'credito_id', 'abono_capital')
                ->where('credito_id', $credito->id)
                ->get();
            $capital = 0;

            foreach ($abonosCliente as $abono) {
                if (!empty($abono->abono_capital)) {
                    $capital += $abono->abono_capital ?? 0;
                } else {
                    // Calcular capital cubierto por el abono
                    $abonosAsociados = app(new MobileController)->procesarAbonos($abono, true);

                    $ultimoAbono = end($abonosAsociados) ?: [];
                    $capital += $ultimoAbono['detalles']['capital'] ?? 0;
                }
            }

            // Se le suma al cupo el valor abonado por el cliente al capital del credito
            $cupo += $capital;

            // validar si el o los creditos que tenga el cliente vigentes esta en mora
            $proyeccion = $credito->proyecciones->first();
            if ($proyeccion) $enMora = Carbon::today()->gt(Carbon::parse($proyeccion->fecha));

            $tieneCreditos = true;
        }

        if (!$tieneCreditos) {
            $cupo = $cliente->cupo;
        }

        // consulta que aplica unicamente para clientes creados desde el formulario de libranza
        $empresaConvenio = null;
        if ($cliente->cliente_libranza == 1 && $cliente->clienteLibranza) {
            $empresaConvenio = ConvenioLibranza::find($cliente->clienteLibranza->convenio_empresa_id);
        }

        $datos = array(
            'cupo' => $cupo < 0 ? 0 : $cupo,
            'numCreditos' => ($tieneCreditos) ? $creditos->count() . ' crédito(s) vigentes' : ' No tiene créditos vigentes',
            'nota' => $cliente->nota,
            'empresaConvenio' => $empresaConvenio,
            'lineaCredito' => LineasCredito::find($cliente->lineas_credito_id),
            'conCreditos' => ($tieneCreditos) ? ' cupo disponible ' : ' cupo aprobado',
            'enMora' => $enMora
        );

        return response()->json(compact('datos'));
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

    function calcularIntereses($credito) {
        $n = $credito->num_cuotas;
        $p = $credito->valor_compra;

        if ($credito->periocidad != 1) $n *= 2;

        $i = 0;
        $iOtro = 0;
        if ($credito->por_anual && $credito->por_anual != 0) {
            $tasaNominalMensual = (pow(1 + ($credito->por_anual / 100), 1 / 12) - 1) * 100;
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

        $tabla[] = array(
            'saldo' => $p,
        );

        $totalIntereses = 0;

        for ($j = 0; $j < $n; $j++) {
            $intereses = 0;
            if ($j == 0) {
                $proyeccion = $credito->proyecciones->first();

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
                    $diasIntereses = $fechaCredito->diffInDays($fechaPago);

                    if ($diasIntereses == 0 || $diasIntereses == 31 || ($credito->periocidad == 2 && $diasIntereses == 15)) {
                        $intereses = $tabla[$j]['saldo'] * $i;
                    } else {
                        $intereses = (($tabla[$j]['saldo'] * $i) / 30) * $diasIntereses;
                    }
                } else {
                    $intereses = $tabla[$j]['saldo'] * $i;
                }
            } else {
                $intereses = $tabla[$j]['saldo'] * $i;
            }

            if ($intereses) $totalIntereses += round($intereses);

            $otroIntereses =  $tabla[$j]['saldo'] * $iOtro;

            if ($j == 0) {
                $capital = $valCuotaFija - ($tabla[$j]['saldo'] * $i) - $otroIntereses;
            } else {
                $capital = $valCuotaFija - $intereses - $otroIntereses;
            }

            $saldo = $tabla[$j]['saldo'] - $capital;

            $tabla[] = array(
                'saldo' => $saldo
            );
        }

        return $totalIntereses ?? 0;
    }

    public function obtenerEstadoFuncion($nombreFuncion, $empresaId) {
        $funcion = EstadoFunciones::where('nombre_funcion', $nombreFuncion)->first();
        return ParametrosEstadoFunciones::where([
            ['empresa_id', $empresaId],
            ['estado_funcion_id', $funcion->id ?? null]
        ])->exists() ? 1 : 0;
    }

    private function validarCreditosSimultaneos($clienteId)
    {
        $cliente = Cliente::find($clienteId);

        // empresa asociada al cliente
        $empresaCliente = Empresa::find($cliente->empresa_id);

        // consultar empresa principal
        if ($empresaCliente->aliado || $empresaCliente->sede) {
            $empresaCliente = Empresa::find($empresaCliente->aliado ?? $empresaCliente->sede);
        }

        // verificar si esta habilitada la funcion que permite que un cliente pueda tener mas de un credito a la vez
        $creditosSimultaneos = ParametrosEstadoFunciones::where('empresa_id', $empresaCliente->id)
            ->whereHas('estado_funcion', function($query) {
                $query->where('nombre_funcion', 'Restringir créditos simultáneos');
            })
            ->exists();

        if ($creditosSimultaneos) {
            return [
                'continuar' => false,
                'response' => response()->json([
                    'status' => 412,
                    'cliente' => $cliente,
                    'empresa' => $empresaCliente->id,
                    'message' => 'El cliente debe finalizar el pago de sus créditos vigentes antes de solicitar un nuevo crédito.',
                    'redirect' => false
                ])
            ];
        }

        return ['continuar' => true];
    }

    private function validarConsultaCentrales($clienteId)
    {
        $cliente = Cliente::where('id', $clienteId)->first();

        // empresa asociada al cliente
        $empresaCliente = Empresa::find($cliente->empresa_id);

        // consultar empresa principal
        if ($empresaCliente->aliado || $empresaCliente->sede) {
            $empresaCliente = Empresa::where('id', $empresaCliente->aliado ?? $empresaCliente->sede)->first();
        }

        // verificar si esta habilitada la funcion que permite validar la vigencia de la consulta en centrales
        $vigenciaAval = ParametrosEstadoFunciones::where('empresa_id', $empresaCliente->id)
            ->whereHas('estado_funcion', function($query) {
                $query->where('nombre_funcion', 'Actualización consulta en centrales');
            })
            ->exists();

        if (!$vigenciaAval) return ['continuar' => true];

        // meses de vigencia de la consulta
        $vigencia = $empresaCliente->vigencia_aval;

        // notificacion aprobacion consulta en centrales
        $notification = Notification::withTrashed()
            ->where('client_id', $clienteId) // busqueda por cliente
            ->where('type', 'CLIENT_ANALIZED')
            ->orderBy('id', 'desc')
            ->first();

        // consultar historico de autorizaciones
        $historicoAutorizaciones = NuevaAutorizacionConsulta::where('cliente_id', $clienteId)
            ->orderBy('id', 'desc')
            ->first();

        $fechaReferencia = $cliente->firmado ? $cliente->firmado : $cliente->created_at;
        // si el cliente tiene un historico de autorizaciones firmadas, se toma como referencia la fecha de la ultima
        if ($historicoAutorizaciones) $fechaReferencia = $historicoAutorizaciones->created_at;

        if (Carbon::parse($fechaReferencia)->diffInMonths(now()) >= $vigencia) {
            // si aun no se ha generado el archivo de autorizacion porque este no ha sido visualizado por el cliente
            if (!$cliente->url_archivo_autorizacion) {
                $urlAutorizacion = (new ClienteController)->generarArchivoAutorizacion($cliente, true);
                $cliente->update(['url_archivo_autorizacion' => $urlAutorizacion]);
            }

            if ($cliente->nueva_consulta_centrales == 0) {
                $cliente->update([
                    'nueva_autorizacion_consulta' => 1, // bandera para identificar al cliente con nueva autorizacion pendiente
                    'nueva_consulta_centrales' => 1, // bandera para identificar al cliente con nueva consulta
                    'aprobar_autorizacion' => 0,
                    'autorizacion' => 0
                ]);
            }

            $cliente->update([
                'adjuntar_aval' => null,
                'estado_aval' => null,
                'no_aval' => null,
                'nota' => null
            ]);

            if ($notification) $notification->delete();

            return [
                'continuar' => false,
                'response' => response()->json([
                    'status' => 403,
                    'nuevoCupo' => false,
                    'email' => $cliente->email,
                    'clienteId' => $cliente->id,
                    'cedula' => $cliente->cedula,
                    'message' => 'La consulta en centrales aprobada el <strong>' . Carbon::parse($fechaReferencia)->format('d/m/Y') . '</strong> ha expirado. Es necesario actualizarla para procesar un nuevo crédito. <br><br> ¿Desea reenviar la autorización al correo del cliente?'
                ])
            ];
        }

        return ['continuar' => true];
    }

    public function validacionesCliente($cliente)
    {
        $usuario = auth()->user();

        $usuarioId = $usuario->id;
        $empresaId = $usuario->empresa_id;

        $empresaCliente = Empresa::find($empresaId);

        // helper interno retornos
        $respuesta = function ($status, $message, $title, $icon, $extra = []) {
            return [
                'continuar' => false,
                'response' => response()->json(array_merge([
                    'status' => $status,
                    'message' => $message,
                    'title' => $title,
                    'icon' => $icon
                ], $extra))
            ];
        };

        // rechazado por centrales
        if ($cliente->estado_aval === 0) {
            return $respuesta(412,
                'La consulta realizada en centrales ha sido rechazada, por lo cual no es posible continuar con el proceso de colocación del crédito.',
                'Crédito no autorizado',
                'error',
                ['cliente' => $cliente, 'empresa' => $empresaId, 'redirect' => false]
            );
        }

        // pendiente autorizacion consulta en centrales
        if (empty($cliente->aprobar_autorizacion)) {
            return $respuesta(403,
                'El cliente aún no ha autorizado la consulta en centrales de riesgo.<br><br>¿Desea enviar la solicitud de autorización al correo electrónico: ' . ($cliente->email ?? '') . '?',
                'Autorización pendiente',
                'warning',
                [
                    'email' => $cliente->email,
                    'clienteId' => $cliente->id,
                    'cedula' => $cliente->cedula
                ]
            );
        }

        // cliente no validado
        if ($cliente->cliente_validado == 0 && !$empresaCliente->inactivar_validacion) {
            // $vEmpresaCliente = Empresa::find($cliente->empresa_id);
            $validacionParametros = ParametrosEstadoFunciones::where('empresa_id', $empresaId)
                ->whereHas('estado_funcion', function($query) {
                    $query->where('nombre_funcion', 'Validación cliente');
                })
                ->exists();

            // parametros pendientes por validar (por rol)
            $pendientes = [];
            $mapValidacion = [
                'validacion_telefono' => 'Teléfono',
                'validacion_referencias' => 'Referencias',
                'validacion_cedula' => 'Foto de la cédula (imágen frontal y posterior)',
                'validacion_tarjeta_propiedad' => 'Foto de la tarjeta de propiedad (imágen frontal y posterior)'
            ];

            if ($validacionParametros) {
                $permisos =  UsuarioTipoUsuario::where('id_usuario', $usuarioId)
                    ->join('subtipousuario', 'subtipousuario.id', '=', 'usuario_tipo_usuario.id_tipo_usuario')
                    ->select('subtipousuario.id', 'subtipousuario.nombre')
                    ->pluck('id')->toArray();


                foreach ($mapValidacion as $campo => $nombre) {
                    if ($empresaCliente->$campo == 1) {
                        $rol = $empresaCliente->{'rol_' . $campo};
                        if (!$rol || in_array($rol, $permisos)) $pendientes[] = $nombre;
                    }
                }

                // si la funcion esta activa pero no se ha checkeado ningun parametro por defecto se obligara a validar las referencias
                if (empty($pendientes)) $pendientes[] = 'Referencias';
            } else {
                // por defecto si esta inactiva la funcion de validacion cliente, se obliga a validar las referencias (funcionalidad legacy)
                $pendientes[] = 'Referencias';
            }

            if (!empty($pendientes)) {
                $mensaje = 'Para generar el crédito, es necesario validar los documentos del cliente:';
                // $mensaje .= '<br><br>La siguiente información debe ser validada antes de generar el crédito:<br><br><ul style="list-style-type: none; margin: 0; padding: 0;">';
                $mensaje .= '<br><br><ul style="list-style-type: none; margin: 0; padding: 0;">';
                foreach ($pendientes as $item) {
                    $mensaje .= '<li style="font-weight: 600;">- ' . $item . '</li>';
                }

                $mensaje .= '</ul>';
                // $mensaje .= '<br>¿Desea realizar el proceso de validación?';
                $mensaje .= '<br>Haga clic en aceptar para iniciar la validación.';

                return $respuesta(412,
                    $mensaje,
                    'Validación pendiente',
                    'warning',
                    ['cliente' => $cliente, 'empresa' => $empresaId, 'redirect' => true, 'parametro_ruta' => 'validacion_datos']
                );
            }
        }

        // consulta en centrales pendiente de aprobación
        if (empty($cliente->estado_aval)) {
            return $respuesta(412,
                'La consulta realizada en centrales de riesgo está pendiente de aprobación por parte del usuario responsable de esta gestión.',
                'Consulta pendiente de aprobación',
                'warning',
                ['cliente' => $cliente, 'empresa' => $empresaId, 'redirect' => false]
            );
        }

        // foto del cliente no adjuntada
        if (empty($cliente->comprobar_cliente)) {
            return $respuesta(412,
                'La foto del cliente está pendiente.<br><br>Para continuar, por favor sube una foto.',
                'Foto pendiente',
                'warning',
                [
                    'cliente' => $cliente,
                    'empresa' => $empresaId,
                    'redirect' => true,
                    'textAccept' => 'Subir foto',
                    'parametro_ruta' => 'foto_cliente'
                ]
            );
        }

        return [ 'continuar' => true ];
    }

    public function updateMora(Request $request) {
        $empresaId = auth()->user()->empresa_id;

        $limiteCreditos = 50;
        $estadoFuncion = ParametrosEstadoFunciones::where('empresa_id', $empresaId)
            ->whereHas('estado_funcion', function($query) {
                $query->where('nombre_funcion', 'Mora un día más');
            })
            ->exists();

        // Obtener créditos de aliados
        $listaAliados = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id')
            ->prepend($empresaId)
            ->unique()
            ->toArray();

        $filtroCreditosCartera = Credito::whereIn('empresa_id', $listaAliados)
            ->whereHas('proyeccionesCartera');

        $actualizarIds = (clone $filtroCreditosCartera)
            ->when($estadoFuncion, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('fecha_gcobranza_temp')
                        ->orWhereDate('fecha_gcobranza_temp', '<', Carbon::today());
                });

            }, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('fecha_gcobranza')
                        ->orWhereDate('fecha_gcobranza', '<', Carbon::today());
                });
            })
            ->limit($limiteCreditos)
            ->pluck('id')
            ->toArray();

        if (!empty($actualizarIds)) {
            if ($estadoFuncion) {
                $fechaMañana = Carbon::now()->addDay()->startOfDay();
                $this->calculoCobranzaIntMoraTemp($actualizarIds, $fechaMañana);
                Credito::whereIn('id', $actualizarIds)->update(['fecha_gcobranza_temp' => Carbon::now()]);
            } else {
                $this->calculoCobranzaIntMora($actualizarIds);
                Credito::whereIn('id', $actualizarIds)->update(['fecha_gcobranza' => Carbon::now()]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Estado de la mora actualizado correctamente',
                'moraPendiente' => count($actualizarIds) >= $limiteCreditos ? true : false
            ], 200);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'No hay créditos pendientes por actualizar',
                'moraPendiente' => false
            ], 200);
        }
    }
}
