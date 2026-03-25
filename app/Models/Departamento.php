<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Departamento
 *
 * @property int $id
 * @property string $nombre
 * @property int|null $codDane
 * @property int $pais_idpais
 *
 * @package App\Models
 */
class Departamento extends Model
{
	protected $table = 'departamento';
	public $timestamps = false;

	protected $casts = [
		'codDane' => 'int',
		'pais_idpais' => 'int'
	];

	protected $fillable = [
		'nombre',
		'codDane',
		'pais_idpais'
	];

    public function pais() {
        return $this->belongsTo(Pais::class, 'pais_idpais');
    }
}
