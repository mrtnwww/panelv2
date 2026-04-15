<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Banco
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $tipo
 * @property string|null $img_icon
 *
 * @package App\Models
 */
class Banco extends Model
{
	protected $table = 'bancos';
	public $timestamps = false;

	protected $fillable = [
		'nombre',
		'tipo',
		'img_icon'
	];
}
