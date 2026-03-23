<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuario';
	public $timestamps = false;

    protected $fillable = [
		'correo',
		'password',
		'subtipousuario_id',
		'persona_id',
		'empresa_id',
		'update_at',
		'image',
		'client_id',
        'ult_acceso',
        'bloqueado'
	];

    protected $hidden = [
        'password',
    ];

    public function persona()
    {
        // return $this->belongsTo(Persona::class, 'persona_id');
    }
}
