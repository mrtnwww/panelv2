<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmpresasAvalistas extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'empresas_avalistas';

    protected $fillable = [
        'nombre_empresa',
        'nit_empresa',
        'empresa_id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
