<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReporteCentralesHistorial
 *
 * @property int $id
 * @property int $user_id
 * @property int $credito_id
 * @property int $tipo_reporte_id
 * @property Carbon|null $fecha_reporte
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class ReporteCentralesHistorial extends Model
{
	protected $table = 'reporte_centrales_historial';
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'credito_id' => 'int',
		'tipo_reporte_id' => 'int'
	];

	protected $dates = [
		'fecha_reporte'
	];

	protected $fillable = [
		'user_id',
		'credito_id',
		'tipo_reporte_id',
		'fecha_reporte'
	];
}
