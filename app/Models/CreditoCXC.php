<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditoCXC extends Model
{
    use HasFactory;

    protected $table = 'credito_cxc';
    public $timestamps = false;

    protected $fillable = [
        'credito_id',
        'producto_id',
        'recibo_caja_id',
        'created_at',
    ];
}
