<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteLibranza extends Model
{
    public $timestamps = false;
    protected $table = 'cliente_libranza';

    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'cliente_id',
        'ciudad_expedicion_id',
        'ciudad_nacimiento_id',
        'convenio_empresa_id',
        'cuota_estimada',
        'egresos',
        'estado_civil_id',
        'estrato',
        'fecha_expedicion',
        'genero_id',
        'monto_solicitado',
        'personas_cargo',
        'plazo',
        'posee_activos',
        'valor_activos',
        'tipo_documento',
        'tipo_vivienda_id',
        'created_at'
    ];

    public function scopeApplySearch($query, $searchTerm)
    {
        if (!empty($searchTerm)) {
            $query->where(function($subQuery) use ($searchTerm) {
                $fields = ['email', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'telefono', 'cedula', 'created_at'];
                foreach ($fields as $field) {
                    $subQuery->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }
    }

    public function scopeApplyAliado($query, $aliadoId)
    {
        if (!empty($aliadoId)) {
            $query->where('empresa_id', $aliadoId);
        }
    }

    public function scopeApplyConditions($query, $conditions, $validarDatos)
    {
        if (count($conditions) > 0) {
            foreach ($conditions as $field => $value) {
                if ($field != 'iscontinue') {
                    $query->where($field, $value)->where('iscontinue', 0); // El proceso aun no se encuentra finalizado
                } else {
                    $query->where($field, $value);
                }
            }
        }

        if (!$validarDatos) {
            $query->whereHas('referenciaCliente', function ($query) {
                $query->where('iscontinue', 0) // El proceso aun no se encuentra finalizado
                    ->where(function ($q) {
                        $q->whereNull('res_ref_comecial_1')
                            ->WhereNull('res_ref_comecial_2')
                            ->WhereNull('res_ref_familiar_1')
                            ->WhereNull('res_ref_familiar_2');
                    });
            });
        }
    }

    public function scopeApplyOrWhereConditions($query, $searchTerm, $empresasAliadas, $aliadoId, $empresaId)
    {
        // Validar si el usuario tiene empresas aliadas para aplicar el filtro
        if ($empresasAliadas->isNotEmpty()) {
            $query->orWhere(function($subQuery) use ($searchTerm, $empresasAliadas, $aliadoId) {
                $subQuery->whereIn('empresa_id', $empresasAliadas)
                    ->applySearch($searchTerm)
                    ->applyAliado($aliadoId);
            });
        } else {
            $query->orWhere(function($subQuery) use ($searchTerm, $aliadoId, $empresaId) {
                $subQuery->where('empresa_id', $empresaId)
                    ->applySearch($searchTerm)
                    ->applyAliado($aliadoId);
            });
        }
    }

    public function credito(): HasOne
    {
        return $this->hasOne(Credito::class, 'client_id');
    }

    public function referenciaCliente(): HasOne
    {
        return $this->hasOne(ReferenciaCliente::class, 'cliente_id');
    }

    public function firma_cliente (): HasMany
    {
        return $this->hasMany(FirmaCliente::class, 'cliente_id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class,'cliente_id');
    }

    public function ConvenioLibranza()
    {
        return $this->belongsTo(ConvenioLibranza::class, 'convenio_empresa_id');
    }

}
