<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Documento extends Model
{
    /**
     * Nombre de la tabla
     */
    protected $table = 'documentos';

    const FIXED_DOCUMENTS = [
        [
            'id' => 'contrato_credigital',
            'nombre' => 'Contrato credigital'
        ],
        [
            'id' => 'tyc',
            'nombre' => 'Términos & Condiciones'
        ],
        [
            'id' => 'autori_trat_datos',
            'nombre' => 'Autorización tratamiento de datos personales'
        ],
        [
            'id' => 'autori_centrales',
            'nombre' => 'Autorización centrales de riesgo'
        ],
        [
            'id' => 'firm_electronica',
            'nombre' => 'Firma electrónica'
        ]
    ];

    /**
     * Campos que pueden asignarse
     */
    protected $fillable = [
        'empresa_id',
        'nombre',
        'path',
        'estado',
        'tipo_documento_id',
        'deleted',
        'key',
        'updated_at'
    ];

    /**
     * Casteo de datos
     */
    protected $casts = [
        'estado' => 'boolean'
    ];

    protected $appends = [
        'temp_path'
    ];

    protected $hidden = [
        'deleted'
    ];

    /**
     * Listado de relaciones
     */
    protected $with = [
        'tipo_documento'
    ];

    protected $active_to_current_company;

    /**
     * Crear nuevo campo con url temporal al archivo
     */
    public function getTempPathAttribute ()
    {
        $expiracion = Carbon::now()->addMinutes(30); // Establecer la expiración en 5 minutos
        if (isset($this->attributes['path'])) {
            return Storage::disk('s3')->temporaryUrl($this->attributes['path'], $expiracion);
        }
    }

    /**
     * Relación del tipo de documento
     */
    public function tipo_documento ()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function empresas ()
    {
        return $this->hasMany(ParametrosDocumento::class, 'documento_id');
    }

    public static function getFixedDocuments ()
    {
        return self::FIXED_DOCUMENTS;
    }

}
