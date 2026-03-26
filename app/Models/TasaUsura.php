<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasaUsura extends Model
{
    use HasFactory;

    protected $table = 'tasa_usura';
    protected $fillable = [
        'fecha',
        'interes'
    ];
}
