<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class NotificationHasUser
 *
 * @property int $id
 * @property int $notification_id
 * @property int $user_id
 * @property Carbon|null $visualized_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class NotificationHasUser extends Model
{
	use SoftDeletes;
	protected $table = 'notification_has_user';
	public $timestamps = false;

	protected $casts = [
		'notification_id' => 'int',
		'user_id' => 'int'
	];

	protected $dates = [
		'visualized_at'
	];

	protected $fillable = [
		'notification_id',
		'user_id',
		'visualized_at'
	];
}
