<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

use App\Models\Empresa;
use App\Models\FacturacionElectronica;

class FacturacionElectronicaService
{
    /*********************************************************************
     * Generar un cliente HTTP para la pasarela de facturación electrónica
     */
    public function client($userEmpresa)
    {
        $empresa = Empresa::select('id', 'aliado', 'sede')
            ->findOrFail($userEmpresa);

        $empresaId = $empresa->aliado
            ?? $empresa->sede
            ?? $empresa->id;

        if ($empresa->id !== $empresaId) {
            $empresa = Empresa::findOrFail($empresaId);
        }

        $configFE = FacturacionElectronica::where('empresa_id', $empresa->id)
            ->first();

        if (!$configFE) return null;

        return [
            'empresa' => $empresa,
            'http'    => Http::withToken(decrypt($configFE->token)),
            'baseUrl' => $configFE->url,
        ];
    }
}

