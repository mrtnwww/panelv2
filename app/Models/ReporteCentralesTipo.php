<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReporteCentralesTipo
 *
 * @property string $id
 * @property string $tipo_reporte
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class ReporteCentralesTipo extends Model
{
	protected $table = 'reporte_centrales_tipo';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'tipo_reporte'
	];
}
