<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class FacturacionElectronica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'facturacion_electronica';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'nombre',
        'token',
        'url'
    ];

}
