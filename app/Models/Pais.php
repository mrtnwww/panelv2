<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Pai
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $codigo
 * @property string|null $image
 * @property string $alias
 *
 * @package App\Models
 */
class Pais extends Model
{
	protected $table = 'pais';
	public $timestamps = false;

	protected $fillable = [
		'nombre',
		'codigo',
		'image',
		'alias'
	];
}
