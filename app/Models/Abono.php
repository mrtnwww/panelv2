<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Abono
 *
 * @property int $id
 * @property int $credito_id
 * @property int $user_id
 * @property int $valor
 * @property Carbon $created_at
 * @property string|null $observaciones
 * @property int $tipo_pago
 * @property int $consecutivo
 * @property int|null $valor_mora
 *
 * @package App\Models
 */
class Abono extends Model
{
    use SoftDeletes;
	protected $table = 'abono';
	public $timestamps = false;

	protected $casts = [
		'credito_id' => 'int',
		'user_id' => 'int',
		'valor' => 'int',
		'tipo_pago' => 'int',
		'consecutivo' => 'int',
		'valor_mora' => 'int'
	];

	protected $fillable = [
		'credito_id',
		'user_id',
		'valor',
        'abono_credito',
        'abono_gas_cobranza',
        'abono_iva_gas_cobranza',
        'abono_int_mora',
		'observaciones',
		'tipo_pago',
		'consecutivo',
		'valor_mora',
        'abono_factura',
        'abono_concepto_capital'
	];


    public function condonaciones(){
        return $this->hasMany(Condonacion::class,'abono_id');
    }

    public function scopeApplyConditions($query, $conditions) {
        if (count($conditions) > 0) {
            $query->where(function($subQuery) use ($conditions) {
                if (!empty($conditions['abonoAval'])) {
                    $subQuery->whereNotNull('abono_aval')
                        ->where('abono_aval', '>', 0);
                }

                if (!empty($conditions['tipo_pago'])) {
                    $subQuery->WhereHas('tipoPago', function ($clienteQuery) use ($conditions) {
                        $clienteQuery->where('id', $conditions['tipo_pago']);
                    });
                }


                if (!empty($conditions['cliente'])) {
                    $subQuery->WhereHas('credito.cliente', function ($clienteQuery) use ($conditions) {
                        $clienteQuery->where('id', $conditions['cliente']);
                    });

                }

                if (!empty($conditions['cajera'])) {
                    $subQuery->WhereHas('user.persona', function ($userQuery) use ($conditions) {
                        $userQuery->where('id', $conditions['cajera']);
                    });
                }

                if (!empty($conditions['aliado'])) {
                    $subQuery->WhereHas('credito.empresa', function ($empresaQuery) use ($conditions) {
                        $empresaQuery->where('id', $conditions['aliado']);
                    });
                }

                if (!empty($conditions['fecha_inicial']) || !empty($conditions['fecha_final'])) {
                    $fechaInicial = !empty($conditions['fecha_inicial']) ? Carbon::parse($conditions['fecha_inicial'])->startOfDay()->addHours(5) : null;
                    $fechaFinal = !empty($conditions['fecha_final']) ? Carbon::parse($conditions['fecha_final'])->endOfDay()->addHours(5) : null;

                    if ($fechaInicial && $fechaFinal) {
                        $subQuery->whereBetween('created_at', [$fechaInicial, $fechaFinal]);
                    } elseif ($fechaInicial) {
                        $subQuery->where('created_at', '>=', $fechaInicial);
                    } elseif ($fechaFinal) {
                        $subQuery->where('created_at', '<=', $fechaFinal);
                    }
                }

                if (!empty($conditions['diasMoraDesde']) || !empty($conditions['diasMoraHasta'])) {
                    $diasMoraDesde = !empty($conditions['diasMoraDesde']) ? $conditions['diasMoraDesde'] : null;
                    $diasMoraHasta = !empty($conditions['diasMoraHasta']) ? $conditions['diasMoraHasta'] : null;

                    $subQuery->whereNotNull('dias_mora')->where('dias_mora', '>', 0);

                    if ($diasMoraDesde && $diasMoraHasta) {
                        $subQuery->whereBetween('dias_mora', [$diasMoraDesde, $diasMoraHasta]);
                    } elseif ($diasMoraDesde) {
                        $subQuery->where('dias_mora', '>=', $diasMoraDesde);
                    } elseif ($diasMoraHasta) {
                        $subQuery->where('dias_mora', '<=', $diasMoraHasta);
                    }
                }
            });
        }
    }

    public function scopeApplySearch($query, $searchTerm) {
        if (!empty($searchTerm)) {
            $query->where(function($subQuery) use ($searchTerm) {
                $fields = ['created_at', 'valor', 'observaciones'];
                foreach ($fields as $field) {
                    $subQuery->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }

                $subQuery->orWhereHas('tipoPago', function ($pagoQuery) use ($searchTerm) {
                    $pagoQuery->where('nombre', 'LIKE', '%' . $searchTerm . '%');
                });

                $subQuery->orWhereHas('credito.cliente', function ($creditQuery) use ($searchTerm) {
                    $creditQuery->where('nombre', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('cedula', 'LIKE', '%' . $searchTerm . '%');
                });

                $subQuery->orWhereHas('user.persona', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('nombre', 'LIKE', '%' . $searchTerm . '%');
                });

                $subQuery->orWhereHas('credito.empresa', function ($empresaQuery) use ($searchTerm) {
                    $empresaQuery->where('razon_social', 'LIKE', '%' . $searchTerm . '%');
                });

                $subQuery->orWhereHas('credito', function ($empresaQuery) use ($searchTerm) {
                    $empresaQuery->where('id', 'LIKE', '%' . $searchTerm . '%');
                });
            });
        }
    }

    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago');
    }

    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id')->withTrashed();
    }
}
