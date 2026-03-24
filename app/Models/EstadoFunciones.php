<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoFunciones extends Model
{
    use HasFactory;
    protected $table = 'estado_funciones';
    protected $fillable = [
        'nombre_funcion',
        'estado',
        'descripcion'
    ];

    public function parametros_estado_funciones(){
        return $this->hasMany(ParametrosEstadoFunciones::class,'estado_funcion_id');
    }

}
