<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsuarioTipoUsuario
 *
 * @property int $id_usuario
 * @property int $id_tipo_usuario
 *
 * @package App\Models
 */
class UsuarioTipoUsuario extends Model
{
	protected $table = 'usuario_tipo_usuario';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id_usuario' => 'int',
		'id_tipo_usuario' => 'int'
	];

	protected $fillable = [
		'id_usuario',
		'id_tipo_usuario'
	];
}
