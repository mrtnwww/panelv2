<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvenioLibranza extends Model
{
    use HasFactory;
    protected $table = 'convenios_libranza';

    protected $fillable = [
        'nit',
        'nombre',
        'representante',
        'cod_convenio',
        'direccion',
        'telefono',
        'num_empleados_convenio',
        'tipos_contrato',
        'cargos',
        'monto_minimo',
        'monto_maximo',
        'plazo_minimo',
        'plazo_maximo',
        'tasa_interes',
        'primer_nombre_contacto_1',
        'segundo_nombre_contacto_1',
        'primer_apellido_contacto_1',
        'segundo_apellido_contacto_1',
        'tipo_documento_contacto_1',
        'cargo_contacto_1',
        'correo_contacto_1',
        'telefono_contacto_1',
        'documento_1',
        'primer_nombre_contacto_2',
        'segundo_nombre_contacto_2',
        'primer_apellido_contacto_2',
        'segundo_apellido_contacto_2',
        'tipo_documento_contacto_2',
        'cargo_contacto_2',
        'correo_contacto_2',
        'telefono_contacto_2',
        'documento_2',
        'primer_nombre_contacto_3',
        'segundo_nombre_contacto_3',
        'primer_apellido_contacto_3',
        'segundo_apellido_contacto_3',
        'tipo_documento_contacto_3',
        'cargo_contacto_3',
        'correo_contacto_3',
        'telefono_contacto_3',
        'documento_3',
        'convenio_vigente',
        'fecha_inicio_convenio',
        'fecha_vigencia_convenio',
        'tipo_empresa',
        'empresa_id',
        'usuario_id',
        'cedula_representante',
        'camara_comercio',
        'documento_rut',
        'created_at',
        'updated_at'
    ];

    public function scopeApplySearch($query, $searchTerm)
    {
        if (!empty($searchTerm)) {
            $query->where(function($subQuery) use ($searchTerm) {
                $fields = ['cod_convenio', 'nit', 'nombre', 'primer_nombre_contacto_1', 'segundo_nombre_contacto_1', 'primer_apellido_contacto_1', 'segundo_apellido_contacto_1', 'correo_contacto_1', 'telefono_contacto_1'];
                foreach ($fields as $field) {
                    $subQuery->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }
    }

    //Relacion con la tabla clientes_libranza
    public function clientesLibranza()
    {
        return $this->hasMany(ClienteLibranza::class, 'convenio_empresa_id');
    }
}
