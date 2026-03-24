<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Credito
 *
 * @property int $id
 * @property int $user_id
 * @property int $client_id
 * @property int $valor_compra
 * @property int $valor_credito
 * @property int $num_cuotas
 * @property int $val_cuotas
 * @property int $periocidad
 * @property Carbon $created_at
 * @property int $consecutivo
 * @property string|null $deleted_at
 * @property string|null $placa
 * @property int $enviar_reporte
 * @property int|null $notificar
 * @property string|null $motivo_anulacion
 * @property int $notificarPlanPagos
 * @property int|null $empresa_id
 * @property string $por_nominal
 * @property string $por_anual
 * @property string $por_plataforma
 * @property string $firma_elec
 * @property string $por_otros
 * @property string $val_otros
 * @property string $aval_porcentaje
 * @property string $aval_value
 * @property string $isexention
 * @property string $valueExention
 * @property Carbon|null $fecha_cierre
 * @property int $notificarCentral
 *
 * @package App\Models
 */
class Credito extends Model
{
	use SoftDeletes;
	protected $table = 'credito';
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'client_id' => 'int',
		'valor_compra' => 'int',
		'valor_credito' => 'int',
		'num_cuotas' => 'int',
		'val_cuotas' => 'int',
		'periocidad' => 'int',
		'consecutivo' => 'int',
		'enviar_reporte' => 'int',
		'notificar' => 'int',
		'notificarPlanPagos' => 'int',
		'empresa_id' => 'int',
		'notificarCentral' => 'int'
	];

	protected $dates = [
		'fecha_cierre'
	];

	protected $fillable = [
		'user_id',
		'client_id',
		'valor_compra',
		'valor_credito',
		'num_cuotas',
		'val_cuotas',
		'periocidad',
		'consecutivo',
		'placa',
        'observacion',
		'enviar_reporte',
		'notificar',
		'motivo_anulacion',
		'notificarPlanPagos',
		'empresa_id',
		'por_nominal',
        'por_anual',
        'otro_por_ea',
        'otro_por_nm',
        'firma_elec',
		'por_plataforma',
		'por_otros',
		'val_otros',
		'aval_porcentaje',
		'aval_value',
		'isexention',
		'valueExention',
		'fecha_cierre',
		'notificarCentral',
        'lineas_credito_id',
        'valor_intereses',
        'otros_sin_dividir'
	];

    public function scopeApplySearch($query, $searchTerm, $form)
    {
        if (!empty($searchTerm)) {
            $query->where(function($subQuery) use ($searchTerm, $form) {
                if (strtoupper(trim($searchTerm)) === 'MENSUAL' ||
                    strtoupper(trim($searchTerm)) === 'QUINCENAL') {
                    $periocidad = strtoupper(trim($searchTerm)) === 'MENSUAL' ? 1 : 2;
                    $subQuery->orWhere('periocidad', 'LIKE', '%' . $periocidad . '%');
                } else {
                    $fields = ['consecutivo', 'valor_compra', 'valor_credito', 'created_at', 'placa', 'producto', 'valor_cxc'];
                    foreach ($fields as $field) {
                        $subQuery->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }

                    $subQuery->orWhereHas('cliente', function ($clienteQuery) use ($searchTerm) {
                        $clienteQuery->where('nombre', 'LIKE', '%' . $searchTerm . '%')
                            ->orWhere('cedula', 'LIKE', '%' . $searchTerm . '%');
                    });
                    $subQuery->orWhereHas('empresa', function ($empresaQuery) use ($searchTerm) {
                        $empresaQuery->where('razon_social', 'LIKE', '%' . $searchTerm . '%');
                    });

                    if (isset($form) && $form === 'comisiones') {
                        $subQuery->orWhereHas('user.persona', function ($empresaQuery) use ($searchTerm) {
                            $empresaQuery->where('nombre', 'LIKE', '%' . $searchTerm . '%');
                        });
                    }
                }
            });
        }
    }

    public function scopeApplySearchAdmin($query, $searchTerm)
    {
        if (!empty($searchTerm)) {
            $query->where(function($subQuery) use ($searchTerm) {
                $subQuery->orWhere('credito.created_at', 'LIKE', '%' . $searchTerm . '%');

                $subQuery->orWhereHas('empresa', function ($empresaQuery) use ($searchTerm) {
                    $empresaQuery->where('razon_social', 'LIKE', '%' . $searchTerm . '%');
                });
            });
        }
    }

    public function scopeApplyConditions($query, $conditions, $form = null) {
        if (count($conditions) > 0) {
            $query->where(function($subQuery) use ($conditions, $form) {
                if (!empty($conditions['estado_credito'])) {
                    $hoy = now()->toDateString();

                    // En mora
                    if ($conditions['estado_credito'] == 1) {
                        $subQuery->whereHas('proyecciones', function($proyeccionQuery) use ($hoy) {
                            $proyeccionQuery->where('pagado', 0)
                                ->whereDate('fecha', '<', $hoy);
                        });
                    }

                    // Normal
                    if ($conditions['estado_credito'] == 2) {
                        $subQuery->whereNull('deleted_at')
                        ->whereDoesntHave('proyecciones', function($proyeccionQuery) use ($hoy) {
                            $proyeccionQuery->where('pagado', 0)
                                ->whereDate('fecha', '<', $hoy);
                        })
                        ->whereHas('proyecciones', function($proyeccionQuery) {
                            $proyeccionQuery->where('pagado', 0);
                        });
                    }

                    // Anulado
                    if ($conditions['estado_credito'] == 3) {
                        $subQuery->whereNotNull('deleted_at');
                    }

                    // Finalizado
                    if ($conditions['estado_credito'] == 4) {
                        $subQuery->whereDoesntHave('proyecciones', function($proyeccionQuery) {
                            $proyeccionQuery->where('pagado', 0);
                        });
                    }
                }

                if (!empty($conditions['cliente'])) {
                    $subQuery->WhereHas('cliente', function ($clienteQuery) use ($conditions) {
                        $clienteQuery->where('id', $conditions['cliente']);
                    });
                }

                if (!empty($conditions['aliado'])) {
                    $subQuery->WhereHas('empresa', function ($empresaQuery) use ($conditions) {
                        $empresaQuery->where('razon_social', 'LIKE', '%' . $conditions['aliado'] . '%')
                            ->orWhere('id', $conditions['aliado']);
                    });
                }

                // tipo de credito
                if (!empty($conditions['destino'])) {
                    $subQuery->where('lineas_credito_id', $conditions['destino']);
                }

                // periodicidad empresa
                if (!empty($conditions['periodicidad'])) {
                    $subQuery->WhereHas('empresa', function ($empresaQuery) use ($conditions) {
                        $empresaQuery->where('periodicidad_empresa', $conditions['periodicidad']);
                    });
                }

                if (!empty($conditions['convenio'])) {
                    $subQuery->whereHas('clienteLibranza.ConvenioLibranza', function ($q) use ($conditions) {
                        $q->where('id', $conditions['convenio']); // O usa 'codigo' si estás filtrando por código
                    });
                }


                if (!empty($conditions['cxc_pendientes'])) {
                    if ($conditions['cxc_pendientes'] == 1) {
                        $subQuery->where(function ($query) {
                            $query->where('valor_cxc', 0)
                                ->orWhereNull('valor_cxc');
                        });
                    } else if ($conditions['cxc_pendientes'] == 2) {
                        $subQuery->where(function ($query) {
                            $query->where('valor_cxc', '>', 0)
                                ->whereNotNull('valor_cxc');
                        });
                    }
                }

                if (!empty($conditions['usuario'])) {
                    $subQuery->WhereHas('user.persona', function ($empresaQuery) use ($conditions) {
                        $empresaQuery->where('id', $conditions['usuario']);
                    });
                }

                if (!empty($conditions['mora'])) {
                    $nextMora = [
                        15 => 30,
                        30 => 60,
                        60 => 90,
                        90 => 120,
                    ];

                    $v = $nextMora[$conditions['mora']] ?? null;

                    $subQuery->whereIn('credito.id', function ($query) use ($conditions, $v) {
                        $query->select('credito_id')
                            ->from('credito_proyeccion')
                            ->where('pagado', 0)
                            ->groupBy('credito_id')
                            ->havingRaw('MAX(diasMora) > ?', [$conditions['mora']])
                            ->when($conditions['mora'] < 120, function ($query) use ($v) {
                                $query->havingRaw('MAX(diasMora) < ?', [$v]);
                            });
                    });
                }

                if (!empty($conditions['mes_reporte'])) {
                    $subQuery->whereMonth('credito.created_at', Carbon::parse($conditions['mes_reporte'])->month)
                        ->whereYear('credito.created_at', Carbon::parse($conditions['mes_reporte'])->year);
                }

                if (!empty($conditions['fecha_inicial']) || !empty($conditions['fecha_final'])) {
                    $fechaInicial = !empty($conditions['fecha_inicial']) ? Carbon::parse($conditions['fecha_inicial'])->startOfDay()->addHours(5) : null;
                    $fechaFinal = !empty($conditions['fecha_final']) ? Carbon::parse($conditions['fecha_final'])->endOfDay()->addHours(5) : null;

                    if ($fechaInicial && $fechaFinal) {
                        $subQuery->whereBetween('credito.created_at', [$fechaInicial, $fechaFinal]);
                    } elseif ($fechaInicial) {
                        $subQuery->where('credito.created_at', '>=', $fechaInicial);
                    } elseif ($fechaFinal) {
                        $subQuery->where('credito.created_at', '<=', $fechaFinal);
                    }
                }

                // consultar los creditos por fecha de vencimiento de cuota
                if (!empty($conditions['valorFechaVenceDesde']) || !empty($conditions['valorFechaVenceHasta'])) {
                    $fechaVenceDesde = !empty($conditions['valorFechaVenceDesde']) ? Carbon::parse($conditions['valorFechaVenceDesde'])->startOfDay() : null;
                    $fechaVenceHasta = !empty($conditions['valorFechaVenceHasta']) ? Carbon::parse($conditions['valorFechaVenceHasta'])->endOfDay() : null;

                    $subQuery->whereIn('credito.id', function ($query) use ($fechaVenceDesde, $fechaVenceHasta) {
                        $query->select('cp.credito_id')
                            ->from('credito_proyeccion as cp')
                            ->join(DB::raw('(SELECT credito_id, MIN(fecha) as primera_fecha
                                            FROM credito_proyeccion
                                            WHERE pagado = 0
                                            GROUP BY credito_id) as primeras_cuotas'), function($join) {
                                $join->on('cp.credito_id', '=', 'primeras_cuotas.credito_id')
                                    ->on('cp.fecha', '=', 'primeras_cuotas.primera_fecha');
                            })
                            ->where('cp.pagado', 0)
                            ->when($fechaVenceDesde && $fechaVenceHasta, function ($q) use ($fechaVenceDesde, $fechaVenceHasta) {
                                $q->whereBetween('cp.fecha', [$fechaVenceDesde, $fechaVenceHasta]);
                            })
                            ->when($fechaVenceDesde && !$fechaVenceHasta, function ($q) use ($fechaVenceDesde) {
                                $q->where('cp.fecha', $fechaVenceDesde);
                            })
                            ->when(!$fechaVenceDesde && $fechaVenceHasta, function ($q) use ($fechaVenceHasta) {
                                $q->where('cp.fecha', '<=', $fechaVenceHasta);
                            });
                    });
                }

                // informe comisiones
                // if (!empty($form) && $form == 'comisiones') {
                //     $subQuery->whereNotNull('user_comision')
                //         ->where('user_comision', '!=', '')
                //         ->where('user_comision', '>', 0);
                // }
            });
        }
    }

    public function scopeApplyConditionsCobranza($query, $conditions) {
        if (!empty($conditions)) {
            $applyRangeFilter = function ($query, $desde, $hasta, $expr) {
                $columna = DB::raw($expr);

                if ($desde !== null && $hasta === null) {
                    $query->where($columna, '=', $desde);
                } elseif ($desde !== null && $hasta !== null) {
                    $query->whereBetween($columna, [$desde, $hasta]);
                } elseif ($desde === null && $hasta !== null) {
                    $query->where($columna, '<=', $hasta);
                }
            };

            if (!empty($conditions['meses_pagados']) || !empty($conditions['cuotas_pagadas'])) {
                $mesesDesde = $conditions['meses_pagados']['desde'] ?? null;
                $mesesHasta = $conditions['meses_pagados']['hasta'] ?? null;

                $cuotasDesde = $conditions['cuotas_pagadas']['desde'] ?? null;
                $cuotasHasta = $conditions['cuotas_pagadas']['hasta'] ?? null;

                $needsJoin = ($mesesDesde !== null || $mesesHasta !== null || $cuotasDesde !== null || $cuotasHasta !== null);

                if ($needsJoin) {
                    $hoy = Carbon::now();
                    $estadoCredito = $conditions['estado_credito'] ?? null;
                    $query->leftJoinSub(function ($q) use ($estadoCredito, $hoy) {
                        $q->from('credito_proyeccion')
                            ->select(DB::raw('credito_id, count(*) as cuotas_pagadas'))
                            ->groupBy('credito_id');

                            if ($estadoCredito == 1) {
                                $q->where('pagado', 0)
                                ->where('fecha', '<', $hoy);
                            } else {
                                $q->where('pagado', 1);
                            }
                    }, 'proyecciones_count', 'credito.id', '=', 'proyecciones_count.credito_id');

                    if ($mesesDesde || $mesesHasta) {
                        $mesesExpr = 'COALESCE(proyecciones_count.cuotas_pagadas, 0) / (CASE WHEN credito.periocidad = 2 THEN 2 ELSE 1 END)'; // 2 periodicidad quincenal (2 pagos cubren un mes)
                        $applyRangeFilter($query, $mesesDesde, $mesesHasta, $mesesExpr);
                    }

                    if ($cuotasDesde || $cuotasHasta) {
                        $cuotasExpr = 'COALESCE(proyecciones_count.cuotas_pagadas, 0)';
                        $applyRangeFilter($query, $cuotasDesde, $cuotasHasta, $cuotasExpr);
                    }
                }
            }

            // filtro notificacion
            if (!empty($conditions['notificacion'])) {
                $hoy = Carbon::now();

                $method = $conditions['notificacion'] == 1 ? 'whereHas' : 'whereDoesntHave';

                $query->$method('notificaciones', function ($notificacionesQuery) use ($hoy) {
                    $notificacionesQuery->whereNotNull('correo_id')
                        ->whereBetween('created_at', [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()]);
                });
            }

            // filtro reporte
            if (!empty($conditions['reporte']) && $conditions['reporte'] != 'none') {
                $query->whereHas('reporteCentralesHistorial', function ($reporteQuery) use ($conditions) {
                    $reporteQuery->where('tipo_reporte_id', $conditions['reporte']);
                });
            }

            // filtro mes corte
            if (!empty($conditions['mes_corte'])) {
                $fechaCorte = Carbon::parse($conditions['mes_corte'] . '-01')->endOfMonth()->toDateTimeString();
                $query->where('created_at', '<=', $fechaCorte);
            }

            // cantidad de dias en mora
            if (!empty($conditions['dias_mora'])) {
                $diasDesde = $conditions['dias_mora']['desde'] ?? null;
                $diasHasta = $conditions['dias_mora']['hasta'] ?? null;

                if ($diasDesde !== null || $diasHasta !== null) {
                    $query->leftJoinSub(function ($q) {
                        $q->from('credito_proyeccion')
                        ->select(DB::raw('credito_id, MAX(diasMora) as dias_mora_max'))
                        ->where('pagado', 0)
                        ->groupBy('credito_id');
                    }, 'proyecciones_max', 'credito.id', '=', 'proyecciones_max.credito_id');

                    if ($diasDesde !== null && $diasHasta === null) {
                        $query->where('proyecciones_max.dias_mora_max', '=', $diasDesde);
                    } elseif ($diasDesde !== null && $diasHasta !== null) {
                        $query->whereBetween('proyecciones_max.dias_mora_max', [$diasDesde, $diasHasta]);
                    } elseif ($diasDesde === null && $diasHasta !== null) {
                        $query->where('proyecciones_max.dias_mora_max', '<=', $diasHasta);
                    }
                }
            }

            // fecha de vencimiento de la cuota
            if (!empty($conditions['vencimiento_cuota'])) {
                $vencimientoDesde = $conditions['vencimiento_cuota']['desde'] ?? null;
                $vencimientoHasta = $conditions['vencimiento_cuota']['hasta'] ?? null;

                $query->whereExists(function ($q) use ($vencimientoDesde, $vencimientoHasta) {
                    $q->select(DB::raw(1))
                    ->from('credito_proyeccion as cp')
                    ->whereColumn('cp.credito_id', 'credito.id')
                    ->where('cp.pagado', 0);

                    if ($vencimientoDesde && !$vencimientoHasta) {
                        $q->whereDate('cp.fecha', '=', $vencimientoDesde);

                    } elseif ($vencimientoDesde && $vencimientoHasta) {
                        $q->whereBetween('cp.fecha', [$vencimientoDesde, $vencimientoHasta]);

                    } elseif (!$vencimientoDesde && $vencimientoHasta) {
                        $q->whereDate('cp.fecha', '<=', $vencimientoHasta);
                    }
                });
            }

            if (!empty($conditions['estado_cliente_tarea'])) {
                $query->WhereHas('cliente', function ($clienteQuery) use ($conditions) {
                    $clienteQuery->where('estado_cliente_tarea', $conditions['estado_cliente_tarea']);
                });
            }
        }
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class, 'credito_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'client_id');
    }

    public function empresa(): BelongsTo {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function proyecciones(): HasMany
    {
        return $this->hasMany(CreditoProyeccion::class, 'credito_id');
    }

    public function proyeccionesCartera(): HasMany
    {
        return $this->hasMany(CreditoProyeccion::class)
                    ->where('pagado', 0)
                    ->whereDate('fecha', '<', now());
                    // ->where('diasMora', '>', 0);
    }

    public function proyeccionPendiente()
    {
        return $this->hasOne(CreditoProyeccion::class)
            ->where('pagado', 0)
            ->orderBy('fecha', 'asc');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionCentralRiesgo::class, 'credito_id');
    }

    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id')->withTrashed();
    }

    public function clienteLibranza()
    {
        return $this->belongsTo(ClienteLibranza::class, 'client_id', 'cliente_id');
    }

    public function lineasCredito() {
        return $this->belongsTo(LineasCredito::class)->withTrashed();
    }

    public function reporteCentralesHistorial()
    {
        return $this->hasMany(ReporteCentralesHistorial::class, 'credito_id');
    }
}
