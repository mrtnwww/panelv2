<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ReferenciaCliente
 *
 * @property int $id
 * @property int $cliente_id
 * @property string|null $ref_comecial_1
 * @property string|null $ref_comecial_2
 * @property string|null $ref_familiar_1
 * @property string|null $ref_familiar_2
 * @property string|null $res_ref_comecial_1
 * @property string|null $res_ref_comecial_2
 * @property string|null $res_ref_familiar_1
 * @property string|null $res_ref_familiar_2
 * @property string|null $tel_1
 * @property string|null $tel_2
 * @property string|null $tel_3
 * @property string|null $tel_4
 * @property string|null $com_1
 * @property string|null $com_2
 * @property string|null $com_3
 * @property string|null $com_4
 *
 * @package App\Models
 */
class ReferenciaCliente extends Model
{
	protected $table = 'referencia_cliente';
	public $timestamps = false;

	protected $casts = [
		'cliente_id' => 'int'
	];

	protected $fillable = [
		'cliente_id',
		'ref_comecial_1',
		'ref_comecial_2',
		'ref_familiar_1',
		'ref_familiar_2',
		'res_ref_comecial_1',
		'res_ref_comecial_2',
		'res_ref_familiar_1',
		'res_ref_familiar_2',
		'tel_1',
		'tel_2',
		'tel_3',
		'tel_4',
		'com_1',
		'com_2',
		'com_3',
		'com_4'
	];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
