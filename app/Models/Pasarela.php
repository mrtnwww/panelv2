<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Pasarela
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $banco_id
 * @property string|null $enlace
 * @property string $public_api_key
 * @property string|null $observacion
 * @property Carbon $created_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class Pasarela extends Model
{
	use SoftDeletes;
	protected $table = 'pasarela';
	public $timestamps = false;

	protected $casts = [
		'empresa_id' => 'int',
		'banco_id' => 'int'
	];

	protected $fillable = [
		'empresa_id',
		'banco_id',
		'enlace',
		'public_api_key',
		'observacion',
        'secret_pasarela',
        'user_id_pasarela',
        'activa'
	];
}
