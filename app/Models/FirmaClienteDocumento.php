<?php

namespace App\Models;

use App\Utils\Encryptation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FirmaClienteDocumento extends Model
{
    protected $table = 'firma_cliente_documentos';

    protected $fillable = [
        'firma_cliente_id',
        'documento_id',
        'documento_general_id',
        'viewed_at',
        'signed_at',
    ];

    protected $appends = [
        'nombre_documento',
        'is_document_adjustment',
        'created_at_formatted',
        'viewed_at_formatted',
        'signed_at_formatted',
    ];

    protected $id_encrypted;

    public function getNombreDocumentoAttribute ()
    {
        $nombreDocumento = null;
        if (isset($this->attributes['documento_id'])) {
            $documento = Documento::find($this->attributes['documento_id']);
            $nombreDocumento = $documento ? $documento->nombre : null;
        }
        return $nombreDocumento;
    }

    public function getIsDocumentAdjustmentAttribute ()
    {
        return $this->attributes['documento_general_id'] === null && $this->attributes['documento_id'] !== null
            ? 1 : 0;
    }

    public function adjunto ()
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }

    public function getCreatedAtFormattedAttribute ()
    {
        if (isset($this->attributes['created_at'])) {
            return Carbon::parse($this->attributes['created_at'])->format('d M Y - h:i A');
        }
    }

    public function getViewedAtFormattedAttribute ()
    {
        if (isset($this->attributes['viewed_at'])) {
            return Carbon::parse($this->attributes['viewed_at'])->format('d M Y - h:i A');
        }
    }

    public function getSignedAtFormattedAttribute ()
    {
        if (isset($this->attributes['signed_at'])) {
            return Carbon::parse($this->attributes['signed_at'])->format('d M Y - h:i A');
        }
    }
}
