<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CodeudorCliente
 *
 * @property int $id
 * @property string $cedula_cliente_id
 * @property string $cedula_codeudor_client_id
 *
 * @package App\Models
 */
class CodeudorCliente extends Model
{
	protected $table = 'codeudor_cliente';
	public $timestamps = false;

	protected $fillable = [
		'cedula_cliente_id',
		'cedula_codeudor_client_id'
	];
}
