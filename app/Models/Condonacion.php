<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condonacion extends Model
{
    use HasFactory;
    protected $table = 'condonaciones';
    protected $fillable = [
        'valor_condonado',
        'abono_id',
        'usuario_id'
    ];


    public function abono(){
        return $this->belongsTo(Abono::class);
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class)->withTrashed();
    }

}
