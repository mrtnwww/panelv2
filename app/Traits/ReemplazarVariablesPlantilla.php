<?php

namespace App\Traits;

use App\Models\Ciudad;

trait ReemplazarVariablesPlantilla
{
    public function reemplazarVariablesPlantillaCorreo($template, $convenio = null, $empresa = null, $cliente = null)
    {
        $ciudadCliente = Ciudad::find($cliente->ciudad);

        $variables = [];

        if ($cliente) {
            $variables['cliente_correo']     = $cliente->email;
            $variables['cliente_cedula']     = $cliente->cedula;
            $variables['cliente_nombre']     = $cliente->nombre;
            $variables['cliente_salario']    = $cliente->salario;
            $variables['cliente_telefono']   = $cliente->telefono;
            $variables['cliente_direccion']  = $cliente->direccion;
            $variables['cliente_tel_empresa']= $cliente->telEmpresa;
            $variables['cliente_empresa']    = $cliente->empresa_labora;
            $variables['cliente_nacimiento'] = $cliente->fecha_nacimiento;
            $variables['cliente_dir_empresa']= $cliente->direccionEmpresa;
            $variables['cliente_ciudad']     = $ciudadCliente ? $ciudadCliente->nombre : '';
            $variables['cliente_registro']   = \Carbon\Carbon::parse($cliente->created_at)->format('d/m/Y');
        }

        if ($convenio) {
            $variables['convenio_nit']            = $convenio->nit;
            $variables['convenio_nombre']         = $convenio->nombre;
            $variables['convenio_telefono']       = $convenio->telefono;
            $variables['convenio_direccion']      = $convenio->direccion;
            $variables['convenio_codigo']         = $convenio->cod_convenio;
            $variables['convenio_representante']  = $convenio->representante;
            $variables['convenio_vigencia']       = \Carbon\Carbon::parse($convenio->fecha_vigencia_convenio)->format('d/m/Y');
        }

        if ($empresa) {
            $variables['empresa_nit']              = $empresa->nit;
            $variables['empresa_direccion']        = $empresa->direccion;
            $variables['empresa_razon_social']     = $empresa->razon_social;
            $variables['empresa_representante']    = $empresa->representante;
            $variables['empresa_correo_comercial'] = $empresa->correo_comercial;
            $variables['empresa_tel_comercial']    = $empresa->telefono_comercial;
            $variables['empresa_ciudad']           = $empresa->ciudad ? $empresa->ciudad->nombre : '';
        }

        $texto = $template;

        $texto_final = preg_replace_callback(
            '/<span class="template-variable shadow"[^>]*id="([^"]+)"[^>]*>.*?<\/span>/i',
            function ($matches) use ($variables) {
                $clave = $matches[1];
                return $variables[$clave] ?? '';
            },
            $texto
        );

        return $texto_final;
    }
}