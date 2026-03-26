<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ParametrosInterese
 *
 * @property int $id
 * @property string $interes_ea
 * @property string $interes_nm
 * @property string $aval_nominal
 * @property string $aval_porcentual
 * @property string $aval_iva
 * @property string|null $aval_documento
 * @property string $otros_nominal
 * @property string $otros_porcentual
 * @property string $otros_observacion
 * @property string $valor_consulta
 * @property int $empresa_id
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon|null $update_at
 * @property string $firma_elec
 * @property string $isexention
 * @property string $valueExention
 * @property bool $isMora
 * @property int $periodicidad
 *
 * @package App\Models
 */
class ParametrosInterese extends Model
{
    use SoftDeletes;

    const INTERES_MODE_GENERAL = 'gen';
    const INTERES_MODE_INDIVIDUAL = 'ind';

	protected $table = 'parametros_intereses';
	public $timestamps = false;

	protected $casts = [
		'empresa_id' => 'int',
		'user_id' => 'int',
		'isMora' => 'bool',
		'periodicidad' => 'int'
	];

    protected $appends = [
        'intereses_mode_is_general'
    ];

	protected $dates = [
		'update_at'
	];

    protected $fillable = [
        'otros_observacion',
        'update_at',
        'empresa_id',
        'user_id',
        'valor_comision',
        'valor_consulta',
        'interes_mode',
        'isPlatformCheck',
        'valueExention',
        'valueExentionGracia',
        'periodicidad',
        'aval_documento',
        'otro_por_observacion',
        'firma_elec_porcentual',
        'redondeo_intereses',
        'otros_porcentual',
        'empresa_avalista',
        'aval_porcentual',
        'firma_elec_iva',
        'otros_nominal',
        'aval_columnas',
        'aval_nominal',
        'otro_por_nm',
        'otro_por_ea',
        'interes_ea',
        'interes_nm',
        'isexention',
        'isexentionGracia',
        'firma_elec',
        'aval_iva',
        'isMora',
        'lineas_credito_id',
        'otros_sin_dividir',
        'restar_aval'
    ];

    public function getInteresesModeIsGeneralAttribute ()
    {
        return isset($this->attributes['interes_mode']) && $this->attributes['interes_mode'] === 'gen';
    }
}
