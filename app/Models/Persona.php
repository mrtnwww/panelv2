<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'persona';

    protected $casts = [
		'ciudad_id' => 'int'
	];

    protected $fillable = [
		'nombre',
		'direccion',
		'contacto',
		'ciudad_id'
	];
}
