<?php

namespace App\Http\Controllers\Empresas;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\RegistroAliado;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function listMyCompanys(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $tab = $request->input('tab');
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);

        if (!empty($tab) && in_array($tab, ['alianzas_firmadas', 'alianzas_pendientes'])) {
            $empresas = RegistroAliado::where('id_empresa_principal', $empresaId)
                ->when($tab === 'alianzas_firmadas', fn($q) => $q->whereNotNull('firmado'))
                ->when($tab === 'alianzas_pendientes', fn($q) => $q->whereNull('firmado'))
                ->when(
                    $search,
                    fn($q, $search) =>
                    $q->where('nombre', 'like', "%{$search}%")
                )
                ->orderBy('nombre')
                ->paginate($perPage);
        } else {
            $empresas = Empresa::query()
                ->select(['id', 'razon_social', 'correo', 'aliado', 'sede', 'periodicidad_empresa'])
                ->when(empty($tab), function ($query) use ($empresaId) {
                    $query->where(function ($q) use ($empresaId) {
                        $q->where('id', $empresaId)
                            ->orWhere('sede', $empresaId)
                            ->orWhere('aliado', $empresaId);
                    });
                })
                ->when($tab === 'aliados', fn($q) => $q->where('aliado', $empresaId))
                ->when($tab === 'sedes', fn($q) => $q->where('sede', $empresaId))
                ->when(
                    $search,
                    fn($q, $search) =>
                    $q->where('razon_social', 'like', "%{$search}%")
                )
                ->orderBy('razon_social')
                ->paginate($perPage);
        }

        return response()->json([
            'empresas' => $empresas
        ]);
    }

    public function infoEmpresa()
    {
        $empresaId = auth()->user()->empresa_id;

        $empresa = Empresa::with('ciudad.departamento')
            ->select([
                'id',
                'razon_social',
                'nit',
                'direccion',
                'ciudad_id',
                'telefonoComercial',
                'correo_comercial',
                'aliado',
                'sede'
            ])
            ->find($empresaId);

        if (!$empresa) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        // Empresa principal
        $idEmpresaPrincipal = $empresa->aliado ?: ($empresa->sede ?: $empresa->id);

        // Departamento-Ciudad
        $ciudad = $empresa->ciudad;
        $departamento = $ciudad?->departamento;

        $empresa->ciudad_nombre = ($ciudad && $departamento)
            ? "{$departamento->nombre}-{$ciudad->nombre}"
            : null;

        return response()->json([
            'resultado' => [
                'datosEmpresa' => $empresa,
                'idEmpresaPrincipal' => $idEmpresaPrincipal
            ]
        ]);
    }

    public function saveInfoEmpresa(Request $request)
    {
        $request->validate([
            'razon_social'  => 'required|string|max:255',
            'nit'           => 'required|string|max:50',
            'direccion'     => 'required|string|max:255',
            'ciudad_id'     => 'required|exists:ciudad,id',
            'telefono'      => 'required|string|max:20',
            'correo'        => 'required|email|max:255'
        ], [
            'razon_social.required' => 'El nombre de la empresa es obligatorio',
            'nit.required'          => 'El NIT de la empresa es obligatorio',
            'direccion.required'    => 'La dirección de la empresa es obligatoria',
            'ciudad_id.required'    => 'La ciudad es obligatoria',
            'ciudad_id.exists'      => 'La ciudad seleccionada no es válida',
            'telefono.required'     => 'El teléfono de la empresa es obligatorio',
            'correo.required'       => 'El correo de la empresa es obligatorio',
            'correo.email'          => 'El correo no tiene un formato válido'
        ]);

        $empresa = Empresa::find(auth()->user()->empresa_id);

        if (!$empresa) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $empresa->update([
            'razon_social'  => $request->razon_social,
            'nit'           => $request->nit,
            'direccion'     => $request->direccion,
            'ciudad_id'     => $request->ciudad_id,
            'telefonoComercial' => $request->telefono,
            'correo_comercial'  => $request->correo
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/' . $empresa->razon_social);

            $empresa->update([
                'logo' => $path
            ]);
        }

        return response()->json([
            'message' => 'Empresa actualizada correctamente'
        ]);
    }
}
