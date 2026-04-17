<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PagoConsultaInfo
 *
 * @property int $id
 * @property string $valor_pagar
 * @property string|null $pagado_desde
 * @property string|null $metodo_pago
 * @property string|null $observacion
 * @property Carbon $created_at
 * @property Carbon|null $fecha_pagado
 *
 * @package App\Models
 */
class PagoConsultaInfo extends Model
{
	protected $table = 'pago_consulta_info';
	public $timestamps = false;

	protected $dates = [
		'fecha_pagado'
	];

	protected $fillable = [
		'valor_pagar',
		'pagado_desde',
		'metodo_pago',
		'observacion',
		'fecha_pagado'
	];
}
