<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RegistroAliado
 *
 * @property int $id
 * @property string $nombre
 * @property string $nit
 * @property string $correo
 * @property Carbon $created_at
 * @property Carbon|null $firmado
 * @property int|null $plazo
 * @property int|null $publicidad
 * @property string|null $nombreContacto
 * @property string|null $numContacto
 *
 * @package App\Models
 */
class RegistroAliado extends Model
{
	protected $table = 'registro_aliados';
	public $timestamps = false;

	protected $casts = [
		'plazo' => 'int',
		'publicidad' => 'int'
	];

	protected $dates = [
		'firmado'
	];

	protected $fillable = [
		'nombre',
		'nit',
		'correo',
		'firmado',
		'plazo',
		'publicidad',
		'nombreContacto',
		'numContacto'
	];
}
