<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $casts = [
		'ciudad_id'         => 'int',
		'consecutivo'       => 'int',
		'consecutivo_abono' => 'int',
		'contrato'          => 'int',
		'reglamento'        => 'int',
		'correo_contrato'   => 'int',
		'notificar'         => 'int',
		'activado'          => 'int',
		'credigital'        => 'int',
		'credivehiculo'     => 'int',
		'credihipoteca'     => 'int',
		'sedeAliado'        => 'int',
		'aliado'            => 'int',
		'sede'              => 'int'
	];

	protected $dates = [
		'accepted_contrato',
		'accepted_reglamento'
	];

	protected $fillable = [
		'razon_social',
		'nit',
		'direccion',
		'ciudad_id',
		'representante',
		'telefono',
		'correo',
		'consecutivo',
		'consecutivo_abono',
		'unique',
		'contrato',
		'accepted_contrato',
		'reglamento',
		'accepted_reglamento',
		'correo_contrato',
		'notificar',
		'telefonoComercial',
		'cedula',
		'url_contrato',
		'activado',
		'correo_comercial',
		'url_mososos',
		'porcentaje',
		'personalizado',
		'intereses_automatico',
		'logo',
		'credigital',
		'credivehiculo',
		'credihipoteca',
		'sedeAliado',
		'aliado',
		'sede',
        'vigencia_aval',
        'periodicidad_empresa'
	];
}
