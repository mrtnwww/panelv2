<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Empresa
 *
 * @property int $id
 * @property string $razon_social
 * @property string|null $nit
 * @property string $direccion
 * @property int $ciudad_id
 * @property string $representante
 * @property string $telefono
 * @property string $correo
 * @property int $consecutivo
 * @property int $consecutivo_abono
 * @property string|null $unique
 * @property int $contrato
 * @property Carbon|null $accepted_contrato
 * @property int $reglamento
 * @property Carbon|null $accepted_reglamento
 * @property int $correo_contrato
 * @property int $notificar
 * @property string|null $telefonoComercial
 * @property string|null $cedula
 * @property string|null $url_contrato
 * @property int $activado
 * @property string|null $correo_comercial
 * @property string|null $url_mososos
 * @property string|null $porcentaje
 * @property string|null $personalizado
 * @property string|null $intereses_automatico
 * @property string|null $logo
 * @property int $credigital
 * @property int $credivehiculo
 * @property int $credihipoteca
 * @property int $sedeAliado
 * @property int|null $aliado
 * @property int|null $sede
 *
 * @package App\Models
 */
class Empresa extends Model
{
	protected $table = 'empresa';
	public $timestamps = false;

	protected $casts = [
		'ciudad_id' => 'int',
		'consecutivo' => 'int',
		'consecutivo_abono' => 'int',
		'contrato' => 'int',
		'reglamento' => 'int',
		'correo_contrato' => 'int',
		'notificar' => 'int',
		'activado' => 'int',
		'credigital' => 'int',
		'credivehiculo' => 'int',
		'credihipoteca' => 'int',
		'sedeAliado' => 'int',
		'aliado' => 'int',
		'sede' => 'int'
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

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function colores_fuente(){
        return $this->hasOne(ColoresFuente::class);
    }
}
