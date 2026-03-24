<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Producto
 *
 * @property int $id
 * @property string $nombre
 * @property int $precio
 * @property string|null $referencia
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class Producto extends Model
{
	use SoftDeletes;
	protected $table = 'producto';

	protected $casts = [
		'precio' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'precio',
		'referencia',
		'user_id'
	];
}
