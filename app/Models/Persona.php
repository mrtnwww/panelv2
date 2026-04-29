<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Persona
 *
 * @property int $id
 * @property string|null $nombre
 * @property string|null $direccion
 * @property string|null $contacto
 * @property int|null $ciudad_id
 *
 * @package App\Models
 */
class Persona extends Model
{
	protected $table = 'persona';
	public $timestamps = false;

	protected $casts = [
		'ciudad_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'direccion',
		'contacto',
		'ciudad_id'
	];

	public function ciudad(): BelongsTo
	{
		return $this->belongsTo(Ciudad::class, 'ciudad_id');
	}
}
