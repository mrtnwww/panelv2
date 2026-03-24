<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmaCliente extends Model
{

    protected $table = 'firma_cliente';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'codigo_firmado',
        'codigo_due_at',
        'opened_at',
        'sended_at',
        'tipo_pago_id',
    ];

    protected $with = [
        'documentos'
    ];

    public function documentos ()
    {
        return $this->hasMany(FirmaClienteDocumento::class, 'firma_cliente_id');
    }

    public function tipopago ()
    {
        return $this->belongsTo(TipoPago::class, 'tipo_pago_id');
    }

}
