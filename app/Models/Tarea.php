<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Tarea
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $titulo
 * @property int $usuario_asignado
 * @property Carbon|null $fecha_vencimiento
 * @property string $notas
 * @property int $tipo
 * @property int $prioridad_id
 * @property Carbon $created_at
 * @property int|null $client_id
 * @property int $empresa_id
 * @property int $completado
 * @property Carbon|null $fecha_completado
 * @property int $omitir
 * @property string|null $deleted_at
 * @property int|null $tarea_id_reprog
 * @property int|null $user_id_reprog
 *
 * @package App\Models
 */
class Tarea extends Model
{
	use SoftDeletes;
	protected $table = 'tareas';
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'usuario_asignado' => 'int',
		'tipo' => 'int',
		'prioridad_id' => 'int',
		'client_id' => 'int',
		'empresa_id' => 'int',
		'completado' => 'int',
		'omitir' => 'int',
		'tarea_id_reprog' => 'int',
		'user_id_reprog' => 'int'
	];

	protected $dates = [
		'fecha_vencimiento',
		'fecha_completado'
	];

	protected $fillable = [
		'user_id',
		'titulo',
		'usuario_asignado',
		'fecha_vencimiento',
		'notas',
		'tipo',
		'prioridad_id',
		'client_id',
		'empresa_id',
		'completado',
		'fecha_completado',
		'omitir',
		'tarea_id_reprog',
		'user_id_reprog'
	];

	public function scopeApplySearch($query, $searchTerm)
	{
		if (!empty($searchTerm)) {
			$query->where(function($subQuery) use ($searchTerm) {
				$subQuery->orWhereHas('cliente', function ($clienteQuery) use ($searchTerm) {
					$clienteQuery->where('nombre', 'LIKE', '%' . $searchTerm . '%');
				});
			});
		}
	}

	public function scopeApplyConditions($query, $conditions)
	{
		if (count($conditions) > 0) {
            $query->where(function($subQuery) use ($conditions) {
				$applyDateRange = function ($query, $field, $range) {
					$desde = $range['desde'] ?? null;
					$hasta = $range['hasta'] ?? null;

					if (!empty($desde) && empty($hasta)) {
						$query->where($field, '>=', $desde);
					} elseif (!empty($desde) && !empty($hasta)) {
						$query->whereBetween($field, [$desde, $hasta]);
					} elseif (empty($desde)&& !empty($hasta)) {
						$query->where($field, '<=', $hasta);
					}

					return $query;
				};

				// fecha de creacion
				if (!empty($conditions['creacion']['desde']) || !empty($conditions['creacion']['hasta'])) {
					$applyDateRange($subQuery, 'tareas.created_at', $conditions['creacion']);
				}

				// fecha de completado
				if (!empty($conditions['completada']['desde']) || !empty($conditions['completada']['hasta'])) {
					$applyDateRange($subQuery, 'tareas.fecha_completado', $conditions['completada']);
				}

				// fecha de vencimiento
				if (!empty($conditions['vencimiento']['desde']) || !empty($conditions['vencimiento']['hasta'])) {
					$applyDateRange($subQuery, 'tareas.fecha_vencimiento', $conditions['vencimiento']);
				}

				// cliente
				if (!empty($conditions['cliente'])) {
					$subQuery->where('tareas.client_id', $conditions['cliente']);
				}

				// usuario asignado
				if (!empty($conditions['usuario'])) {
					$subQuery->where('tareas.usuario_asignado', $conditions['usuario']);
				}

				// tipo tarea
				if (!empty($conditions['tipo'])) {
					$subQuery->where('tareas.tipo', $conditions['tipo']);
				}

				// estado tarea
				if (!empty($conditions['filtroEstado'])) {
					if ($conditions['filtroEstado'] == 'completadas') {
						$subQuery->whereNotNull('fecha_completado');
					}

					if ($conditions['filtroEstado'] == 'vencidos') {
						$subQuery->whereNull('fecha_completado')
							->whereNotNull('fecha_vencimiento')
							->whereDate('fecha_vencimiento', '<', Carbon::today());
					}

					if ($conditions['filtroEstado'] == 'vencenHoy') {
						$subQuery->whereNull('fecha_completado')
							->whereNotNull('fecha_vencimiento')
							->whereDate('fecha_vencimiento', Carbon::today());
					}

					if ($conditions['filtroEstado'] == 'proximos') {
						$subQuery->whereNull('fecha_completado')
							->whereNotNull('fecha_vencimiento')
							->whereDate('fecha_vencimiento', '>', Carbon::today());
					}
				}
			});
		}
	}

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'client_id');
	}
}
