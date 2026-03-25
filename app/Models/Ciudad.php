<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ciudad
 *
 * @property int $id
 * @property string $nombre
 * @property int|null $codDane
 * @property int $iddepartamento
 * @property int $pais_idpais
 *
 * @package App\Models
 */
class Ciudad extends Model
{
	protected $table = 'ciudad';
	public $timestamps = false;

	protected $casts = [
		'codDane' => 'int',
		'iddepartamento' => 'int',
		'pais_idpais' => 'int'
	];

	protected $fillable = [
		'nombre',
		'codDane',
		'iddepartamento',
		'pais_idpais'
	];

    public function departamento() {
        return $this->belongsTo(Departamento::class, 'iddepartamento');
    }
}
