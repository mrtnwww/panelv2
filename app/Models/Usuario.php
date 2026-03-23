<?php

namespace App\Models;

use App\Models\Empresa;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuario';
	public $timestamps = false;

    protected $casts = [
		'subtipousuario_id' => 'int',
		'persona_id'        => 'int',
		'empresa_id'        => 'int',
		'client_id'         => 'int'
	];

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
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function empresa() {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
