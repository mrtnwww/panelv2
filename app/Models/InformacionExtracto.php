<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformacionExtracto extends Model
{
    use HasFactory;

    protected $table = 'informacion_extracto';

    protected $fillable = [
        'telefono',
        'web',
        'direccion',
        'correo_electronico',
        'observacion_1',
        'observacion_2',
        'color_base',
        'created_at',
        'updated_at',
    ];
}
