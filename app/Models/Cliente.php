<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Cliente
 *
 * @property int $id
 * @property string|null $cedula
 * @property string|null $nombre
 * @property string|null $direccion
 * @property string|null $ciudad
 * @property string|null $telefono
 * @property string|null $cargo
 * @property string|null $telEmpresa
 * @property string|null $email
 * @property string|null $direccionEmpresa
 * @property string|null $fecha_nacimiento
 * @property string $empresa_labora
 * @property string|null $user_id
 * @property Carbon $created_at
 * @property string $salario
 * @property string|null $foto_frontal
 * @property string|null $foto_posterior
 * @property string|null $foto_tarjeta
 * @property int|null $autorizacion
 * @property string|null $token
 * @property Carbon|null $firmado
 * @property int|null $notificar
 * @property string|null $no_aval
 * @property int|null $estado_aval
 * @property string|null $nota
 * @property string|null $adjuntar_aval
 * @property string|null $url_archivo_autorizacion
 * @property int|null $cupo
 * @property int|null $aprobar_autorizacion
 * @property int|null $isformulario
 * @property int|null $producto_id
 * @property int|null $empresa_id
 * @property string|null $foto_tarjeta_posterior
 * @property string|null $selfie
 * @property int|null $notificarAval
 * @property int|null $iscontinue
 * @property string|null $obsproceso
 * @property int|null $pago_consulta
 * @property int|null $pago_consulta_info_id
 * @property string|null $barrio
 * @property string|null $certificacionBancaria
 * @property int|null $valor_consulta
 * @property Carbon|null $dia_pagado
 * @property string|null $obs_cliente
 * @property string|null $comprobar_cliente
 *
 * @package App\Models
 */
class Cliente extends Model
{
	protected $table = 'cliente';
	public $timestamps = false;

	protected $casts = [
		'autorizacion' => 'int',
		'notificar' => 'int',
		'estado_aval' => 'int',
		'cupo' => 'int',
		'aprobar_autorizacion' => 'int',
		'isformulario' => 'int',
		'producto_id' => 'int',
		'empresa_id' => 'int',
		'notificarAval' => 'int',
		'iscontinue' => 'int',
		'pago_consulta' => 'int',
		'pago_consulta_info_id' => 'int',
		'valor_consulta' => 'int'
	];

	protected $dates = [
		'firmado',
		'dia_pagado'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'cedula',
		'nombre',
		'direccion',
		'ciudad',
		'telefono',
		'cargo',
		'telEmpresa',
		'email',
		'direccionEmpresa',
		'fecha_nacimiento',
		'empresa_labora',
		'user_id',
		'salario',
		'foto_frontal',
		'foto_posterior',
		'foto_tarjeta',
		'autorizacion',
		'token',
		'firmado',
		'notificar',
		'no_aval',
		'estado_aval',
		'nota',
		'adjuntar_aval',
		'url_archivo_autorizacion',
		'cupo',
		'aprobar_autorizacion',
		'isformulario',
		'producto_id',
		'empresa_id',
		'foto_tarjeta_posterior',
		'selfie',
		'notificarAval',
		'iscontinue',
		'obsproceso',
		'pago_consulta',
		'pago_consulta_info_id',
		'barrio',
		'certificacionBancaria',
		'valor_consulta',
		'dia_pagado',
		'obs_cliente',
		'comprobar_cliente',
        'cliente_otp',
        'otp_expiration',
        'counterparty_id',
        'comprobar_cliente_externo',
        'cert_laboral',
        'desprendible_pago',
        'descuento_nomina',
        'documento_soporte',
        'nueva_consulta_centrales',
        'nueva_autorizacion_consulta',
        'obs_validacion',
        'puntaje_consulta',
        'foto_frontal_validada',
        'foto_posterior_validada',
        'foto_tarjeta_validada',
        'foto_tarjeta_posterior_validada',
        'telefono_validado',
        'cliente_validado',
        'estado_cliente_tarea',
        'num_cuenta_bancaria',
        'num_cuenta_bancaria_validada',
        'fecha_inicio_acuerdo_pago',
        'fecha_fin_acuerdo_pago',
        'usuario_acuerdo_pago',
        'debitoAutomatico',
        'autoriza_debito_auto',
        'tipo_cuenta_bancaria',
        'nombre_banco',
        'clienteFE',
        'fecha_visualizacion_formulario',
        'isformularioExpress',
        'empresa_gestion',
        'validar_correo_analisis',
        'validar_telefono_analisis',
        'cliente_validado_automatico'
	];

    public function scopeApplySearch($query, $searchTerm)
    {
        if (!empty($searchTerm)) {
            $query->where(function($subQuery) use ($searchTerm) {
                $fields = ['email', 'nombre', 'cedula', 'telefono', 'created_at'];
                foreach ($fields as $field) {
                    $subQuery->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }
    }

    public function scopeApplyAliado($query, $aliadoId)
    {
        if (!empty($aliadoId)) {
            $query->where('empresa_id', $aliadoId);
        }
    }

    public function scopeApplyCreditoAprobado($query, $param)
    {
        if (!empty($param)) {
            $query->WhereHas('ultCredito', function ($q) {
                $q->whereNotNull('fecha_cierre');
            })->where([
                ['cliente_validado', 1],
                ['autorizacion', 1],
                ['estado_aval', 1],
                ['iscontinue', 0]
            ])->whereNotNull('comprobar_cliente');
        }
    }

    public function scopeApplyRegistroWeb($query, $registroWeb, $validacionAutomatica)
    {
        if (!empty($registroWeb)) {
            $query->where('isformulario', 1);
        }

        if (!empty($validacionAutomatica)) {
            $query->where('cliente_validado_automatico', 1);
        }
    }

    public function scopeApplyRegistroClientes($query, $param)
    {
        if (count($param) > 0) {
            if (!empty($param['fecha_inicial']) || !empty($param['fecha_final'])) {
                $fechaInicial = !empty($param['fecha_inicial']) ? Carbon::parse($param['fecha_inicial'])->startOfDay()->addHours(5) : null;
                $fechaFinal = !empty($param['fecha_final']) ? Carbon::parse($param['fecha_final'])->endOfDay()->addHours(5) : null;

                if ($fechaInicial && $fechaFinal) {
                    $query->whereBetween('created_at', [$fechaInicial, $fechaFinal]);
                } elseif ($fechaInicial) {
                    $query->where('created_at', '>=', $fechaInicial);
                } elseif ($fechaFinal) {
                    $query->where('created_at', '<=', $fechaFinal);
                }
            }
        }
    }

    public function scopeApplyConditions($query, $conditions, $validarDatos)
    {
        if (count($conditions) > 0) {
            foreach ($conditions as $field => $value) {
                if ($field != 'iscontinue') {
                    $query->where($field, $value)->where('iscontinue', 0); // El proceso aun no se encuentra finalizado
                } else {
                    $query->where($field, $value);
                }
            }
        }

        if (!$validarDatos) {
            $query->where('iscontinue', 0)
                ->where('cliente_validado', 0);
        }
    }

    public function scopeApplyOrWhereConditions($query, $searchTerm, $empresasAliadas, $aliadoId, $empresaId, $tipoCliente)
    {
        if ($tipoCliente == 'cliente_libranza') $query->where('cliente_libranza', 1);

        // Validar si el usuario tiene empresas aliadas para aplicar el filtro
        if ($empresasAliadas->isNotEmpty()) {
            $query->orWhere(function($subQuery) use ($searchTerm, $empresasAliadas, $aliadoId) {
                $subQuery->whereIn('empresa_id', $empresasAliadas)
                    ->applySearch($searchTerm)
                    ->applyAliado($aliadoId);
            });
        } else {
            $query->orWhere(function($subQuery) use ($searchTerm, $aliadoId, $empresaId) {
                $subQuery->where('empresa_id', $empresaId)
                    ->applySearch($searchTerm)
                    ->applyAliado($aliadoId);
            });
        }
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function credito(): HasOne
    {
        return $this->hasOne(Credito::class, 'client_id');
    }

    public function ultCredito(): HasOne
    {
        return $this->hasOne(Credito::class, 'client_id')
                    ->latestOfMany();
    }

    public function ciudadInfo(): HasOne {
        return $this->hasOne(Ciudad::class, 'id', 'ciudad');
    }

    public function ciudad(): HasOne {
        return $this->hasOne(Ciudad::class, 'id', 'ciudad');
    }

    public function firma_cliente (): HasMany
    {
        return $this->hasMany(FirmaCliente::class, 'cliente_id');
    }

    public function referenciaCliente(): HasOne
    {
        return $this->hasOne(ReferenciaCliente::class, 'cliente_id');
    }

    public function clienteLibranza(): HasOne
    {
        return $this->hasOne(ClienteLibranza::class, 'cliente_id');
    }
}
