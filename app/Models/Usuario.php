<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

/**
 * Class Usuario
 *
 * @property int $id
 * @property string|null $correo
 * @property string|null $password
 * @property int $subtipousuario_id
 * @property int $persona_id
 * @property int $empresa_id
 * @property Carbon $created_at
 * @property Carbon|null $update_at
 * @property string|null $deleted_at
 * @property string|null $image
 * @property int|null $client_id
 *
 * @package App\Models
 */
class Usuario extends Authenticatable
{
    use SoftDeletes;
    protected $table = 'usuario';
    public $timestamps = false;

    protected $casts = [
        'subtipousuario_id' => 'int',
        'persona_id' => 'int',
        'empresa_id' => 'int',
        'client_id' => 'int'
    ];

    protected $fillable = [
        'correo',
        'password',
        'subtipousuario_id',
        'persona_id',
        'empresa_id',
        'update_at',
        'image',
        'client_id',
        'ult_acceso',
        'bloqueado'
    ];

    protected $hidden = [
        'password',
    ];

    protected function image(): Attribute
    {
        return Attribute::get(function ($value) {
            if (!empty($value) && $value !== "0" && !filter_var($value, FILTER_VALIDATE_URL)) {
                try {
                    return Storage::disk('s3')->temporaryUrl($value, now()->addMinutes(30));
                } catch (\Exception $e) {
                    return null;
                }
            }

            return $value ?: null;
        });
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
