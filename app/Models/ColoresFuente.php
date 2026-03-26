<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColoresFuente extends Model
{
    use HasFactory;
    protected $table = "colores_fuente";
    protected $fillable = [
        'empresa_id',
        'nombre_fuente',
        'color_primaio',
        'color_sugundario'
    ];
}
