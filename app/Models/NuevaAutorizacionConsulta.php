<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NuevaAutorizacionConsulta extends Model
{
    protected $table = 'nueva_autorizacion_consulta';

    protected $fillable = [
        'cliente_id',
        'url_archivo_autorizacion',
        'created_at',
        'updated_at'
    ];
}
