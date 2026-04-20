<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CorreosPlantilla
 *
 * @property int $id
 * @property int $user_id
 * @property int $empresa_id
 * @property string $nombre
 * @property string|null $asunto
 * @property string $texto
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class CorreosPlantilla extends Model
{
	use SoftDeletes;
	protected $table = 'correos_plantillas';

	protected $casts = [
		'user_id' => 'int',
		'empresa_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'empresa_id',
		'nombre',
		'asunto',
		'texto'
	];
}
