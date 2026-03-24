<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametrosEstadoFunciones extends Model
{
    use HasFactory;
    protected $table = 'parametros_estado_funciones';
    protected $fillable = [
        'empresa_id',
        'estado_funcion_id'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class);
    }

    public function estado_funcion(){
        return $this->belongsTo(EstadoFunciones::class);
    }
}
