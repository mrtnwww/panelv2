<?php

namespace App\Http\Controllers\CuentaFacturacion;

use App\Http\Controllers\Controller;
use App\Models\Banco;
use App\Models\Empresa;
use App\Models\EmpresasAvalistas;
use App\Models\LineasCredito;
use App\Models\ParametrosInterese;
use App\Models\Pasarela;
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
        return $this->procesarPersistencia($request);
    }

    public function updateParametros(Request $request)
    {
        return $this->procesarPersistencia($request);
    }

    // Lógica compartida para Crear y Actualizar líneas de crédito
    private function procesarPersistencia(Request $request, $id = null)
    {
        $id = $request->input('id', null);
        $esEdicion = !is_null($id);

        $request->validate([
            'nombre' => 'required|string',
            'periodicidad' => 'required|integer'
        ], [
            'nombre.required' => 'El nombre de la línea de crédito es obligatorio.',
            'periodicidad.required' => 'La periodicidad es obligatoria.'
        ]);

        $empresaId = auth()->user()->empresa_id;
        $usuarioId = auth()->user()->id;

        $parametros = $request->input('parametros', []);

        return DB::transaction(function () use ($request, $empresaId, $usuarioId, $parametros, $id, $esEdicion) {
            // Validar duplicados
            $queryExiste = LineasCredito::where('empresa_id', $empresaId)
                ->where('tipo_credito', $request->nombre);

            if ($esEdicion)
                $queryExiste->where('id', '!=', $id);

            if ($queryExiste->exists()) {
                return response()->json([
                    'message' => "Ya existe una línea con el nombre {$request->nombre}"
                ], 409);
            }

            // Guardar línea de crédito
            $tipoCredito = LineasCredito::updateOrCreate(
                ['id' => $id, 'empresa_id' => $empresaId],
                [
                    'tipo_credito' => strtoupper($request->nombre),
                    'valor_minimo' => $request->valor_minimo ?? 0,
                    'valor_maximo' => $request->valor_maximo ?? 0,
                ]
            );

            // Información empresa avalista
            if ($parametros['aval_enabled']) {
                $avalista = EmpresasAvalistas::withTrashed()->firstOrNew([
                    'empresa_id' => $empresaId,
                    'lineas_credito_id' => $id
                ]);

                if ($avalista->trashed())
                    $avalista->restore();

                $avalista->nit_empresa = $request->parametros['empresa_avalista_nit'];
                $avalista->nombre_empresa = $request->parametros['empresa_avalista'];
                $avalista->lineas_credito_id = $id;
                $avalista->save();
            }

            // Construcción y persistencia de parámetros
            $dataParametros = $this->buildParametrosIntereses(
                $request->periodicidad,
                $parametros,
                $empresaId,
                $usuarioId,
                $tipoCredito->id
            );

            ParametrosInterese::updateOrCreate(
                ['lineas_credito_id' => $tipoCredito->id],
                $dataParametros
            );

            return response()->json([
                'message' => $esEdicion ? 'Línea actualizada exitosamente.' : 'Línea creada exitosamente.',
                'data' => $tipoCredito
            ], $esEdicion ? 200 : 201);
        });
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

    private function buildParametrosIntereses($periodicidad, $p, $empresaId, $usuarioId, $lineaId)
    {
        // Lógica de control checks activos
        $otrosIntActivos = $p['otros_intereses_enabled'] ?? false;
        $firmaActiva = $p['firma_electronica_enabled'] ?? false;
        $intHabilitados = $p['intereses_enabled'] ?? false;
        $otrosActivos = $p['otros_enabled'] ?? false;
        $avalActivo = $p['aval_enabled'] ?? false;

        // Aplicar valores numéricos intereses si esta habilitado el check y modo es individual
        $esIndividual = ($p['tipo_interes'] ?? '') === 'individual';
        $aplicaValorInt = $intHabilitados && !$esIndividual;

        return [
            'lineas_credito_id' => $lineaId,
            'empresa_id' => $empresaId,
            'user_id' => $usuarioId,

            // Periodicidad
            'periodicidad' => $periodicidad ?? 6,

            // Firma electrónica
            'firma_elec_porcentual' => $firmaActiva ? ($p['porcentaje_firma_electronica'] ?? 0) : 0,
            'firma_elec_iva' => $firmaActiva ? ($p['iva_firma_electronica'] ?? 0) : 0,
            'firma_elec' => $firmaActiva ? ($p['firma_electronica'] ?? 0) : 0,

            // Intereses
            'interes_mode' => $intHabilitados ? ($esIndividual ? 'ind' : 'gen') : 'gen',
            'interes_ea' => $aplicaValorInt ? ($p['ea_intereses'] ?? 0) : 0,
            'interes_nm' => $aplicaValorInt ? ($p['nm_intereses'] ?? 0) : 0,

            // Otros intereses
            'otro_por_observacion' => $otrosIntActivos ? ($p['otros_intereses_concepto'] ?? null) : null,
            'otro_por_ea' => $otrosIntActivos ? ($p['ea_otros_intereses'] ?? 0) : 0,
            'otro_por_nm' => $otrosIntActivos ? ($p['nm_otros_intereses'] ?? 0) : 0,

            // Aval
            'aval_porcentual' => $avalActivo ? ($p['porcentaje_aval'] ?? 0) : 0,
            'aval_nominal' => $avalActivo ? ($p['aval'] ?? 0) : 0,
            'aval_iva' => $avalActivo ? ($p['iva_aval'] ?? 0) : 0,
            'empresa_avalista' => $avalActivo ? 1 : 0,

            // Checkbox aval
            'aval_columnas' => $avalActivo ? ($p['mostrar_aval_columnas'] ?? 0) : 0,
            'restar_aval' => $avalActivo ? ($p['restar_aval'] ?? 0) : 0,

            // Otros
            'otros_observacion' => $otrosActivos ? ($p['otros_concepto'] ?? 'Otros') : 'Otros',
            'otros_porcentual' => $otrosActivos ? ($p['porcentaje_otros'] ?? 0) : 0,
            'otros_nominal' => $otrosActivos ? ($p['otros'] ?? 0) : 0,
        ];
    }

    public function getModulos()
    {
        $empresaId = auth()->user()->empresa_id;

        $empresa = Empresa::select('id', 'credivehiculo', 'credihipoteca', 'libranza', 'sedeAliado')->find($empresaId);

        return response()->json([
            'empresa' => $empresa
        ]);
    }

    public function updateModulos(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $campo = $request->modulo;

        $empresa = Empresa::find($empresaId);

        $empresa->$campo = $empresa->$campo ? 0 : 1;
        $empresa->save();

        return response()->json([
            'message' => 'Módulo actualizado correctamente'
        ]);
    }

    public function getPasarelas()
    {
        $pasarelas = Banco::where('tipo', 'pasarela')->get();

        return response()->json([
            'pasarelas' => $pasarelas
        ]);
    }

    public function getPasarelasConfig()
    {
        $empresaId = auth()->user()->empresa_id;

        $pasarelasEmpresa = Pasarela::select('pasarela.*', 'bancos.nombre AS pasarela_nombre')
            ->where('pasarela.empresa_id', $empresaId)
            ->join('bancos', 'bancos.id', '=', 'pasarela.banco_id')
            ->get()
            ->map(function ($item) {
                $item->public_api_key = $item->public_api_key ? true : false;
                $item->secret_pasarela = $item->secret_pasarela ? true : false;
                $item->user_id_pasarela = $item->user_id_pasarela ? true : false;

                return $item;
            });

        return response()->json([
            'pasarelasEmpresa' => $pasarelasEmpresa
        ]);
    }
}
