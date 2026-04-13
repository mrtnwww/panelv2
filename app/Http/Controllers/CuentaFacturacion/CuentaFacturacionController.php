<?php

namespace App\Http\Controllers\CuentaFacturacion;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresasAvalistas;
use App\Models\LineasCredito;
use App\Models\ParametrosInterese;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'nombre.required' => 'El nombre de la línea de crédito es obligatorio.',
            'periodicidad.required' => 'La periodicidad es obligatoria.'
        ]);

        $parametros = $request->parametros ?? [];
        $empresaId = auth()->user()->empresa_id;
        $usuarioId = auth()->user()->id;

        return DB::transaction(function () use ($request, $empresaId, $usuarioId, $parametros) {
            // Validación existencia
            $existe = LineasCredito::where('empresa_id', $empresaId)
                ->where('tipo_credito', $request->nombre)
                ->exists();

            if ($existe) {
                return response()->json([
                    'message' => "Ya existe una línea con el nombre {$request->nombre}"
                ], 409);
            }

            // Crear línea crédito
            $tipoCredito = LineasCredito::create([
                'empresa_id' => $empresaId,
                'tipo_credito' => strtoupper($request->nombre),
                'valor_minimo' => $request->valor_minimo ?? 0,
                'valor_maximo' => $request->valor_maximo ?? 0,
            ]);

            // Construcción limpia de parámetros
            $data = $this->buildParametrosIntereses(
                $parametros,
                $empresaId,
                $usuarioId,
                $tipoCredito->id
            );

            ParametrosInterese::create($data);

            return response()->json([
                'message' => 'Línea de crédito creada exitosamente.'
            ], 201);
        });
    }

    private function buildParametrosIntereses($p, $empresaId, $usuarioId, $lineaId)
    {
        return [
            'lineas_credito_id' => $lineaId,
            'empresa_id' => $empresaId,
            'user_id' => $usuarioId,

            // Periodicidad
            'periodicidad' => $p['periodicidad'] ?? 6,

            // Firma electrónica
            'firma_elec_porcentual' => $p['firma_electronica_enabled'] ? ($p['porcentaje_firma_electronica'] ?? 0) : 0,
            'firma_elec_iva' => $p['firma_electronica_enabled'] ? ($p['iva_firma_electronica'] ?? 0) : 0,
            'firma_elec' => $p['firma_electronica_enabled'] ? ($p['firma_electronica'] ?? 0) : 0,

            // Intereses
            'interes_mode' => ($p['intereses_enabled'] ?? false)
                ? (($p['tipo_interes'] ?? '') === 'individual' ? 'ind' : 'gen')
                : 'gen',
            'interes_ea' => ($p['intereses_enabled'] ?? false) ? ($p['ea_intereses'] ?? 0) : 0,
            'interes_nm' => ($p['intereses_enabled'] ?? false) ? ($p['nm_intereses'] ?? 0) : 0,

            // Otros intereses
            'otro_por_observacion' => $p['otros_intereses_enabled'] ? ($p['otros_intereses_concepto'] ?? null) : null,
            'otro_por_ea' => $p['otros_intereses_enabled'] ? ($p['ea_otros_intereses'] ?? 0) : 0,
            'otro_por_nm' => $p['otros_intereses_enabled'] ? ($p['nm_otros_intereses'] ?? 0) : 0,

            // Aval
            'aval_porcentual' => $p['aval_enabled'] ? ($p['porcentaje_aval'] ?? 0) : 0,
            'aval_nominal' => $p['aval_enabled'] ? ($p['aval'] ?? 0) : 0,
            'aval_iva' => $p['aval_enabled'] ? ($p['iva_aval'] ?? 0) : 0,
            'aval_columnas' => $p['aval_enabled'] ? ($p['mostrar_aval_columnas'] ?? 0) : 0,
            'restar_aval' => $p['aval_enabled'] ? ($p['restar_aval'] ?? 0) : 0,
            'empresa_avalista' => $p['aval_enabled'] ? 1 : 0,

            // Otros
            'otros_observacion' => $p['otros_enabled'] ? ($p['otros_concepto'] ?? 'Otros') : 'Otros',
            'otros_porcentual' => $p['otros_enabled'] ? ($p['porcentaje_otros'] ?? 0) : 0,
            'otros_nominal' => $p['otros_enabled'] ? ($p['otros'] ?? 0) : 0,
        ];
    }

    public function deleteParametros(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $lineaId = $request->input('id', null);

        // Verificar si la linea existe y pertenece al usuario
        $linea = LineasCredito::where('id', $lineaId)->first();

        // eliminar la linea de credito de la tabla parametros_intereses
        $parametroInteres = ParametrosInterese::where('lineas_credito_id', $lineaId)
            ->where('empresa_id', $empresaId)
            ->first();

        // eliminar la linea de credito de la tabla empresas_avalistas
        $empresaAvalista = EmpresasAvalistas::where('empresa_id', $empresaId)
            ->where('lineas_credito_id', $lineaId)
            ->first();

        if ($parametroInteres)
            $parametroInteres->delete(); // Eliminar la linea de credito en parametros intereses
        if ($empresaAvalista)
            $empresaAvalista->delete(); // Eliminar la empresa avalista
        if ($linea)
            $linea->delete(); // Eliminar la linea de credito

        return response()->json([
            'message' => 'Línea de crédito eliminada correctamente.'
        ], 204);
    }
}
