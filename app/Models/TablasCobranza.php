<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TablasCobranza extends Model
{
    use HasFactory;
    protected $table = 'tablas_cobranza';
    protected $fillable = [
        'empresa_id',
        'dias_limit_inf',
        'dias_limit_sup',
        'porcentaje',
        'tipo'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class);
    }
}
