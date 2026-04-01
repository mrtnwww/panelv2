<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Notification
 *
 * @property int $id
 * @property int|null $client_id
 * @property int $empresa_id
 * @property int|null $user_id
 * @property string $title
 * @property string $content
 * @property string|null $url
 * @property string $type
 * @property int|null $visualized_by
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class Notification extends Model
{
    use SoftDeletes;

	protected $table = 'notification';
	public $timestamps = false;

	protected $casts = [
		'client_id' => 'int',
		'empresa_id' => 'int',
		'user_id' => 'int',
		'visualized_by' => 'int'
	];

	protected $fillable = [
		'client_id',
		'empresa_id',
		'user_id',
		'title',
		'content',
		'url',
		'type',
		'visualized_by'
	];
}
