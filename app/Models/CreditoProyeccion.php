<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CreditoProyeccion
 *
 * @property int $id
 * @property int $credito_id
 * @property Carbon $fecha
 * @property int $pagado
 * @property int $valor_mora
 * @property int $diasMora
 *
 * @package App\Models
 */
class CreditoProyeccion extends Model
{
	protected $table = 'credito_proyeccion';
	public $timestamps = false;

	protected $casts = [
		'credito_id' => 'int',
		'pagado' => 'int',
		'valor_mora' => 'int',
		'diasMora' => 'int'
	];

	protected $dates = [
		'fecha'
	];

	protected $fillable = [
		'credito_id',
		'fecha',
		'pagado',
		'valor_mora',
		'diasMora',
        'intereses_moratorios',
        'gastos_cobranza'
	];
}
