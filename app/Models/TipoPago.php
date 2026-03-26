<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TipoPago
 *
 * @property int $id
 * @property string $nombre
 *
 * @package App\Models
 */
class TipoPago extends Model
{
	protected $table = 'tipo_pago';
	public $timestamps = false;

	protected $fillable = [
		'nombre'
	];
}
