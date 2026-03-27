<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class NotificacionCentralRiesgo
 *
 * @property int $id
 * @property int $via_correo
 * @property int $via_sms
 * @property int $via_fisico
 * @property Carbon $created_at
 * @property int $credito_id
 * @property int|null $correo_id
 * @property string|null $soporte_fisico
 *
 * @package App\Models
 */
class NotificacionCentralRiesgo extends Model
{
	protected $table = 'notificacion_central_riesgo';
	public $timestamps = false;

	protected $casts = [
		'via_correo' => 'int',
		'via_sms' => 'int',
		'via_fisico' => 'int',
		'credito_id' => 'int',
		'correo_id' => 'int'
	];

	protected $fillable = [
		'via_correo',
		'via_sms',
		'via_fisico',
		'credito_id',
		'correo_id',
		'soporte_fisico'
	];
}
