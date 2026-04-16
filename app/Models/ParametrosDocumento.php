<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ParametrosDocumento extends Model
{
    /**
     * Nombre de la tabla
     */
    protected $table = 'parametros_documentos';

    /**
     * Campos que pueden asignarse
     */
    protected $fillable = [
        'empresa_id',
        'estado',
        'documento_id',
    ];

    /**
     * Casteo de datos
     */
    protected $casts = [
        'estado' => 'boolean'
    ];

}
