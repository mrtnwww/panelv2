<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Subtipousuario
 *
 * @property int $id
 * @property string $nombre
 * @property int $tipoUsuario_id
 * @property string|null $descripcion
 *
 * @package App\Models
 */
class Subtipousuario extends Model
{
	protected $table = 'subtipousuario';
	public $timestamps = false;

	protected $casts = [
		'tipoUsuario_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'tipoUsuario_id',
		'descripcion'
	];
}
