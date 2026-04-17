<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidacionCuentaBancaria extends Model
{
    protected $table = 'validaciones_cuenta_bancaria';

    protected $fillable = [
        'num_cuenta',
        'cliente_id',
        'usuario_id',
        'estado',
    ];
}
