<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductoCliente
 *
 * @property int $id_producto
 * @property int $id_cliente
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class ProductoCliente extends Model
{
	protected $table = 'producto_cliente';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id_producto' => 'int',
		'id_cliente' => 'int'
	];

	protected $fillable = [
		'id_producto',
		'id_cliente'
	];
}
