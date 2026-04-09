<?php

namespace App\Http\Controllers\CuentaFacturacion;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\LineasCredito;
use App\Models\ParametrosInterese;
use Illuminate\Http\Request;

class CuentaFacturacionController extends Controller
{
    public function getParametros()
    {
        $empresa = Empresa::findOrFail(auth()->user()->empresa_id);

        $empresaPrincipalId = $empresa->aliado ?: ($empresa->sede ?: $empresa->id);

        $lineasCredito = LineasCredito::with([
            'parametros' => function ($query) use ($empresaPrincipalId) {
                $query->where('empresa_id', $empresaPrincipalId);
            },
            'empresaAvalista'
        ])
            ->where(function ($query) use ($empresaPrincipalId) {
                $query->where('empresa_id', $empresaPrincipalId)
                    ->orWhereNull('empresa_id');
            })
            ->orderBy('id')
            ->get();

        if ($lineasCredito->count() > 1) {
            $lineasCredito = $lineasCredito->filter(fn($p) => $p->id != 1)->values();
        }

        return response()->json([
            'lineasCredito' => $lineasCredito
        ]);
    }

    public function saveParametros(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'periodicidad' => 'required|integer'
        ], [
            'nombre' => 'El nombre de la línea de crédito es obligatorio.',
            'periodicidad' => 'La periodicidad es obligatoria.'
        ]);

        $empresaId = auth()->user()->empresa_id;
        $usuarioId = auth()->user()->id;

        // Verificar si ya existe una línea de crédito con el nombre recibido
        $existeCredito = LineasCredito::where('empresa_id', $empresaId)
            ->where(function ($query) use ($request) {
                $query->where('tipo_credito', $request->nombre);
            })
            ->exists();

        if ($existeCredito) {
            return response()->json([
                'message' => 'Ya existe una línea con el nombre ' . $request->nombre
            ], 409);
        }

        // Crear la nueva línea de crédito
        $tipoCredito = new LineasCredito();
        $tipoCredito->empresa_id = $empresaId;
        $tipoCredito->tipo_credito = $request->nombre;
        $tipoCredito->valor_minimo = $request->valor_minimo ?? 0;
        $tipoCredito->valor_maximo = $request->valor_maximo ?? 0;
        $tipoCredito->save();

        $parametros = $request->parametros;
        $parametrosIntereses = new ParametrosInterese;

        // Firma electrónica
        if ($parametros['firma_electronica_enabled']) {
            $parametrosIntereses->firma_elec_porcentual = $parametros['porcentaje_firma_electronica'] ?? 0;
            $parametrosIntereses->firma_elec_iva = $parametros['iva_firma_electronica'] ?? 0;
            $parametrosIntereses->firma_elec = $parametros['firma_electronica'] ?? 0;
        }

        // Intereses
        if ($parametros['intereses_enabled']) {
            $parametrosIntereses->interes_mode = $parametros['tipo_interes'] == 'individual' ? 'ind' : 'gen';
            $parametrosIntereses->interes_ea = $parametros['ea_intereses'] ?? 0;
            $parametrosIntereses->interes_nm = $parametros['nm_intereses'] ?? 0;
        } else {
            $parametrosIntereses->interes_ea = 0;
            $parametrosIntereses->interes_nm = 0;
        }

        // Otros intereses
        if ($parametros['otros_intereses_enabled']) {
            $parametrosIntereses->otro_por_observacion = $parametros['otros_intereses_concepto'] ?? null;
            $parametrosIntereses->otro_por_ea = $parametros['ea_otros_intereses'] ?? 0;
            $parametrosIntereses->otro_por_nm = $parametros['nm_otros_intereses'] ?? 0;
        }

        // Aval
        if ($parametros['aval_enabled']) {
            $parametrosIntereses->aval_porcentual = $parametros['porcentaje_aval'] ?? 0;
            $parametrosIntereses->aval_nominal = $parametros['aval'] ?? 0;
            $parametrosIntereses->aval_iva = $parametros['iva_aval'] ?? 0;
            // $parametrosIntereses->aval_documento = $parametros['aval_documento'] ?? 0;

            $parametrosIntereses->empresa_avalista = 1;

            $parametrosIntereses->aval_columnas = $parametros['mostrar_aval_columnas'];
            $parametrosIntereses->restar_aval = $parametros['restar_aval'];
        } else {
            $parametrosIntereses->aval_porcentual = 0;
            $parametrosIntereses->aval_nominal = 0;
            $parametrosIntereses->aval_iva = 0;
        }

        // Otros
        if ($parametros['otros_enabled']) {
            $parametrosIntereses->otros_nominal = $parametros['otros'] ?? 0;
            $parametrosIntereses->otros_observacion = $parametros['otros_concepto'] ?? 0;
            $parametrosIntereses->otros_porcentual = $parametros['porcentaje_otros'] ?? 0;
        } else {
            $parametrosIntereses->otros_observacion = 0;
            $parametrosIntereses->otros_porcentual = 0;
            $parametrosIntereses->otros_nominal = 0;
        }

        $parametrosIntereses->empresa_id = $empresaId;
        $parametrosIntereses->user_id = $usuarioId;

        $parametrosIntereses->periodicidad = $parametros['periodicidad'] ?? 6;
        $parametrosIntereses->lineas_credito_id = $tipoCredito->id;

        // $parametrosIntereses->valor_consulta = ($request->valorConsulta) ? $request->valorConsulta : '0';
        // $parametrosIntereses->isexention = $request->isexention;
        // $parametrosIntereses->valueExention = $request->valueExention ? $request->valueExention : "";
        // $parametrosIntereses->isexentionGracia = $request->isexentionGracia;
        // $parametrosIntereses->isMora = ($request->interesMoratorio) ? 1 : 0;
        // $parametrosIntereses->periodicidad = $request->tipoCredito['periodicidad'] ?? 6;
        // $parametrosIntereses->valor_comision = $request->valorComision;
        // $parametrosIntereses->redondeo_intereses = $request->redondeoIntereses ? 1 : 0;
        // $parametrosIntereses->otros_sin_dividir = $request->otros_sin_dividir;
        $parametrosIntereses->save();

        return response()->json([
            'message' => 'Línea de crédito creada exitosamente.',
            'lineaCredito' => $tipoCredito,
            'parametrosIntereses' => $parametrosIntereses
        ], 201);
    }
}
