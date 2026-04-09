<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineasCredito extends Model
{
    use SoftDeletes;

    public $table = 'lineas_credito';

    public $fillable = [
        'id',
        'tipo_credito',
        'formulario',
        'valor_minimo',
        'valor_maximo',
        'formulario',
        'empresa_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function parametros()
    {
        return $this->belongsTo(ParametrosInterese::class, 'id', 'lineas_credito_id');
    }

    public function empresaAvalista()
    {
        return $this->hasOne(EmpresasAvalistas::class, 'lineas_credito_id', 'id');
    }

    public function condiciones_registro()
    {
        return $this->hasMany(CondicionRegistro::class, 'linea_credito_id');
    }
}
