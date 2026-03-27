<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReciboCajaCXC extends Model
{
    use HasFactory;

    protected $table = 'recibo_caja_cxc';

    protected $fillable = [
        'empresa_principal_id',
        'id_creditos',
        'empresa_id',
        'created_at',
    ];

    public function scopeApplySearch($query, $searchTerm) {
        if (!empty($searchTerm)) {
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('created_at', 'LIKE', '%' . $searchTerm . '%');

                $subQuery->orWhereHas('empresa', function ($empresaQuery) use ($searchTerm) {
                    $empresaQuery->where('razon_social', 'LIKE', '%' . $searchTerm . '%');
                });
            });
        }
    }

    public function scopeApplyConditions($query, $conditions) {
        if (count($conditions) > 0) {
            $query->where(function($subQuery) use ($conditions) {
                if (!empty($conditions['aliado'])) {
                    $subQuery->where('empresa_id', $conditions['aliado']);
                }

                if (!empty($conditions['fecha_inicial']) || !empty($conditions['fecha_final'])) {
                    $fechaInicial = !empty($conditions['fecha_inicial']) ? Carbon::parse($conditions['fecha_inicial'])->startOfDay() : null;
                    $fechaFinal = !empty($conditions['fecha_final']) ? Carbon::parse($conditions['fecha_final'])->endOfDay() : null;

                    if ($fechaInicial && $fechaFinal) {
                        $subQuery->whereBetween('created_at', [$fechaInicial, $fechaFinal]);
                    } elseif ($fechaInicial) {
                        $subQuery->where('created_at', '>=', $fechaInicial);
                    } elseif ($fechaFinal) {
                        $subQuery->where('created_at', '<=', $fechaFinal);
                    }
                }
            });
        }

    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
