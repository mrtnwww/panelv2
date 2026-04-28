<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&display=swap" rel="stylesheet">

    <style type="text/css">
        @page {
            margin: 0px;
        }

        body {
            margin: 0px;
            padding: 15px;
        }

        * {
            font-family: 'Didact Gothic', sans-serif;
            color: #45525f;
        }

        .fs-13px {
            font-size: 13px;
        }

        .fs-12px {
            font-size: 12px;
        }

        .fs-11px {
            font-size: 11px;
        }

        .fs-10px {
            font-size: 10px;
        }

        .fs-9px {
            font-size: 9px;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        table {
            border-collapse: collapse;
        }

        .table-list {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            text-align: center;
        }

        .table-list thead th {
            color: white;
            padding: .5rem .25rem;
        }

        .table-list tbody td {
            padding: .5rem .25rem;
        }

        .table-list tbody td.border-bottom {
            border-bottom-width: 1px;
            border-bottom-style: solid;
        }

        .table-list tbody td.border-right {
            border-right-width: 1px;
            border-right-style: solid;
        }

        .table-list tbody td.last-child {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .p-envio {
            padding: 0.125rem 1rem;
        }
    </style>
</head>

<body>
    <div>
        <table style="width: 98%;">
            <tr>
                <td style="width: 25%;">
                    <img src="{{ $logo ? $logo : '' }}"
                        style="width: auto; max-width: 200px; height: auto; max-height: 125px;" alt="">
                </td>
                <td class="text-right" style="width: 25%;">
                    <h3 style="color: #45525f; margin: 0;">
                        Extracto de crédito
                    </h3>
                    <div class="fs-11px">
                        {{ \Carbon\Carbon::now()->isoFormat('D MMM YYYY - hh:mm a') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div style="padding: .25rem; border-radius: 15px; background-color: #e9ecef; margin-top: 1rem;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 100%;">
                    <table style="width: 100%;">
                        <tr>
                            <td colspan="3" style="width: 75%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Empresa
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    {{ $empresa['razon_social'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td style="width: 25%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    NIT
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    {{ $empresa['nit'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width: 100%;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Teléfono
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    @if (isset($infoPersonalizadaExtracto) && $infoPersonalizadaExtracto->telefono !== null)
                                                        {{ $infoPersonalizadaExtracto->telefono }}
                                                    @else
                                                        {{ $empresa['telefonoComercial'] }}
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Link de pago
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    @if (isset($infoPersonalizadaExtracto) && $infoPersonalizadaExtracto->web !== null)
                                                        {{ $infoPersonalizadaExtracto->web }}
                                                    @else
                                                        @if ($empresa->nit == '901676539-7')
                                                            www.abanta.co
                                                        @endif
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width: 100%">
                    <table style="width: 100%">
                        <tr>
                            <td colspan="3" style="width: 60%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Dirección
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    @if (isset($infoPersonalizadaExtracto) && $infoPersonalizadaExtracto->direccion !== null)
                                                        {{ $infoPersonalizadaExtracto->direccion }}
                                                    @else
                                                        {{-- {{ $empresa['direccion'] . ' ' . ((isset($empresa['ciudad'])) ? $empresa['ciudad']['nombre'] : '') }} --}}
                                                        {{ $empresa['direccion'] }}
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td colspan="2" style="width: 40%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Correo electrónico
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    @if (isset($infoPersonalizadaExtracto) && $infoPersonalizadaExtracto->correo_electronico !== null)
                                                        {{ $infoPersonalizadaExtracto->correo_electronico }}
                                                    @else
                                                        {{ $empresa['correo_comercial'] }}
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <div style="border-top: 1px dashed #45525f; margin: .5rem 0;"></div>
    <div style="padding: .25rem; border-radius: 15px; background-color: #e9ecef;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 100%;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Número de crédito
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    {{ $credito['id'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Fecha del extracto
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    {{ $fechaCorte->isoFormat('D MMM YYYY') }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Fecha del crédito
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    {{ \Carbon\Carbon::parse($credito['created_at'])->isoFormat('D MMM YYYY - hh:mm a') }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Valor inicial del crédito
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-10px fw-bold">
                                                    ${{ number_format($valorCompra) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width: 100%;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Cliente
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-11px fw-bold">
                                                    {{ $cliente['nombre'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td style="width: 50%; padding: .25rem .125rem;">
                                <div style="padding: 0 1rem; border-radius: 15px; background-color: white;">
                                    <table style="width: 100%">
                                        <tr>
                                            <td>
                                                <span class="fs-11px">
                                                    Identificación
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="fs-11px fw-bold">
                                                    C.C. {{ $cliente['cedula'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <div style="margin-top: 1.5rem;">
        <table class="table-list">
            <thead class="fs-9px">
                <tr>
                    @if ($config['columns']['fecha_cuota'])
                        <th style="background-color: {{ $colorBase }}">Fecha de cuota</th>
                    @endif
                    @if ($config['columns']['consecutivo_abono'])
                        <th style="background-color: {{ $colorBase }}">N° de recibo</th>
                    @endif
                    @if ($config['columns']['fecha_transaccion'])
                        <th style="background-color: {{ $colorBase }}">Fecha transacción</th>
                    @endif
                    @if ($config['columns']['estado'])
                        <th style="background-color: {{ $colorBase }}">Estado</th>
                    @endif
                    @if ($config['columns']['descripcion'])
                        <th style="background-color: {{ $colorBase }}">Descripción</th>
                    @endif
                    @if ($config['columns']['capital'])
                        <th style="background-color: {{ $colorBase }}">Capital</th>
                    @endif
                    @if ($config['columns']['aval'])
                        <th style="background-color: {{ $colorBase }}">Aval</th>
                    @endif
                    @if ($config['columns']['avalIva'])
                        <th style="background-color: {{ $colorBase }}">Aval IVA</th>
                    @endif
                    @if ($config['columns']['int_corriente'])
                        <th style="background-color: {{ $colorBase }}">Int. corriente</th>
                    @endif
                    @if ($config['columns']['firma_elec'])
                        <th style="background-color: {{ $colorBase }}">Firma electrónica</th>
                    @endif
                    @if ($config['columns']['otros'])
                        <th style="background-color: {{ $colorBase }}">Otros</th>
                    @endif
                    @if ($config['columns']['otro_interes'])
                        <th style="background-color: {{ $colorBase }}">Otro interes</th>
                    @endif
                    @if ($config['columns']['int_mora'])
                        <th style="background-color: {{ $colorBase }}">Int. moratorio</th>
                    @endif
                    @if ($config['columns']['gas_cobranza'])
                        <th style="background-color: {{ $colorBase }}">Gastos cobranza</th>
                    @endif
                    @if ($config['columns']['total'])
                        <th style="background-color: {{ $colorBase }}">Valor total</th>
                    @endif
                </tr>
            </thead>
            <tbody class="fs-10px">
                @foreach ($proyeccion as $cuota)
                    <tr>
                        @if ($config['columns']['fecha_cuota'])
                            <td class="fw-bold border-right border-bottom" style="border-color: {{ $colorBase }}">
                                {{ \Carbon\Carbon::parse($cuota->fecha)->isoFormat('D MMM YYYY') }}
                            </td>
                        @endif
                        @if ($config['columns']['consecutivo_abono'])
                            @if ($cuota['rowspan'] !== 0)
                                <td @if ($cuota['rowspan'] !== 1) rowspan="{{ $cuota['rowspan'] }}" @endif
                                    class="fw-bold border-right border-bottom"
                                    style="border-color: {{ $colorBase }}">
                                    @if (isset($cuota['_abono']) && $cuota['_pagado'] === true)
                                        {{ $cuota['_abono']['consecutivo'] }}
                                    @endif
                                </td>
                            @endif
                        @endif
                        @if ($config['columns']['fecha_transaccion'])
                            @if ($cuota['rowspan'] !== 0)
                                <td @if ($cuota['rowspan'] !== 1) rowspan="{{ $cuota['rowspan'] }}" @endif
                                    class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                    @if (isset($cuota['_pagado']) && $cuota['_pagado'] === true)
                                        {{ $cuota['_cuota_pagada_at']->isoFormat('D MMM YYYY') }}
                                    @endif
                                </td>
                            @endif
                        @endif
                        @if ($config['columns']['estado'])
                            @if ($cuota['_pagado'])
                                <td class="border-right border-bottom fw-bold"
                                    style="border-color: {{ $colorBase }} color: #42ba96;">
                                    {{ $cuota['estado'] }}
                                </td>
                            @elseif ($cuota['_mes_fecha_corte'])
                                @isset($cuota['estado'])
                                    <td class="border-right border-bottom fw-bold"
                                        style="border-color: {{ $colorBase }}">
                                        {{ $cuota['estado'] }}
                                    </td>
                                @else
                                    <td class="border-right border-bottom fw-bold"
                                        style="border-color: {{ $colorBase }}">
                                    </td>
                                @endisset
                            @else
                                <td class="border-right border-bottom fw-bold"
                                    style="border-color: {{ $colorBase }}">
                                </td>
                            @endif
                        @endif
                        @if ($config['columns']['descripcion'])
                            @if ($cuota['rowspan'] !== 0)
                                <td @if ($cuota['rowspan'] !== 1) rowspan="{{ $cuota['rowspan'] }}" @endif
                                    class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                    @if (
                                        ((isset($cuota['_pagado']) && $cuota['_pagado'] === true) ||
                                            (isset($cuota['_parcial']) && $cuota['_parcial'] === true)) &&
                                            $cuota['_mes_fecha_corte']
                                    )
                                        {{ $cuota['_cuota_pago_obs'] }}
                                    @endif
                                </td>
                            @endif
                        @endif
                        @if ($config['columns']['capital'])
                            <td class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                @if (isset($cuota['_plan_pagos']) && $cuota['_mes_fecha_corte'])
                                    ${{ number_format($cuota['_capital'] ?? 0) }}
                                @endif
                            </td>
                        @endif
                        @if ($config['columns']['aval'])
                            <td class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                @if (isset($cuota['_plan_pagos']) && $cuota['_mes_fecha_corte'])
                                    ${{ number_format($cuota['_plan_pagos']['aval'] ?? 0) }}
                                @endif
                            </td>
                        @endif
                        @if ($config['columns']['avalIva'])
                            <td class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                @if (isset($cuota['_plan_pagos']) && $cuota['_mes_fecha_corte'])
                                    ${{ number_format($cuota['_plan_pagos']['avalIva'] ?? 0) }}
                                @endif
                            </td>
                        @endif
                        @if ($config['columns']['int_corriente'])
                            <td class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                @if (isset($cuota['_plan_pagos']) && $cuota['_mes_fecha_corte'])
                                    ${{ number_format($cuota['_intereses'] ?? 0) }}
                                @endif
                            </td>
                        @endif
                        @if ($config['columns']['firma_elec'])
                            <td class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                @if (isset($cuota['_plan_pagos']) && $cuota['_mes_fecha_corte'])
                                    ${{ number_format($cuota['_firmaElec'] ?? 0) }}
                                @endif
                            </td>
                        @endif
                        @if ($config['columns']['otros'])
                            <td class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                @if (isset($cuota['_plan_pagos']) && $cuota['_mes_fecha_corte'])
                                    ${{ number_format($cuota['_plan_pagos']['otros'] ?? 0) }}
                                @endif
                            </td>
                        @endif
                        @if ($config['columns']['otro_interes'])
                            <td class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                @if (isset($cuota['_plan_pagos']) && $cuota['_mes_fecha_corte'])
                                    ${{ number_format($cuota['_plan_pagos']['otroIntereses'] ?? 0) }}
                                @endif
                            </td>
                        @endif
                        @if ($config['columns']['int_mora'])
                            @if ($cuota['rowspan'] !== 0)
                                <td @if ($cuota['rowspan'] !== 1) rowspan="{{ $cuota['rowspan'] }}" @endif
                                    class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                    {{ isset($cuota['_abono']['abono_int_mora']) ? '$' . number_format($cuota['_abono']['abono_int_mora']) : '' }}
                                </td>
                            @endif
                        @endif
                        @if ($config['columns']['gas_cobranza'])
                            @if ($cuota['rowspan'] !== 0)
                                <td @if ($cuota['rowspan'] !== 1) rowspan="{{ $cuota['rowspan'] }}" @endif
                                    class="border-right border-bottom" style="border-color: {{ $colorBase }}">
                                    {{ isset($cuota['_abono']['abono_gas_cobranza'])
                                        ? '$' . number_format($cuota['_abono']['abono_gas_cobranza'])
                                        : '' }}
                                </td>
                            @endif
                        @endif
                        @if ($config['columns']['total'])
                            @if ($cuota['rowspan'] !== 0)
                                <td @if ($cuota['rowspan'] !== 1) rowspan="{{ $cuota['rowspan'] }}" @endif
                                    class="border-bottom last-child" style="border-color: {{ $colorBase }}">
                                    @if ($cuota['_mes_fecha_corte'])
                                        {{ isset($cuota['_total_adeudado'])
                                            ? '$' . number_format($cuota['_total_adeudado'])
                                            : (isset($cuota['_total_abonado'])
                                                ? '$' . number_format($cuota['_total_abonado'])
                                                : '') }}
                                    @endif
                                </td>
                            @endif
                        @endif
                    </tr>
                @endforeach
                <tr>
                    <td colspan="{{ $config['num_columns_visible'] - 3 }}"></td>
                    <td class="border-right fw-bold" style="border-color: {{ $colorBase }}" colspan="2">
                        Total pagado a la fecha
                    </td>
                    <td class="border-bottom" style="border-color: {{ $colorBase }}">
                        ${{ number_format($totalAbonadoFechaCorte) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="{{ $config['num_columns_visible'] - 3 }}"></td>
                    <td class="border-right fw-bold" style="border-color: {{ $colorBase }}" colspan="2">
                        Saldo crédito a la fecha
                    </td>
                    <td class="border-bottom" style="border-color: {{ $colorBase }}">
                        ${{ $saldoCredito <= 0 ? 0 : number_format($saldoCredito) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @if (isset($infoPersonalizadaExtracto) && $infoPersonalizadaExtracto->observacion_1 !== null)
        <div class="fs-12px text-center">
            <p>{!! nl2br(e($infoPersonalizadaExtracto->observacion_1)) !!}</p>
        </div>
    @else
        @if ($empresa->nit == '901676539-7')
            <div>
                <p class="fs-9px">
                    <b>AVISO:</b> en caso de no efectuar el pago de su obligación dentro de la fecha prevista y de
                    persistir la mora, la presente comunicación debe entenderse como el preaviso que trata el artículo
                    12 de la Ley
                    1266 de 2008 y artículo 2 de Ley 2157 de 2021, por lo que ABANTA SAS, procederá con el reporte del
                    comportamiento negativo a las centrales de riesgo, con su permanencia y acogiendo los términos
                    establecidos en la Ley.
                </p>
            </div>
            <div class="fs-9px text-center">
                <b>METODOS DE PAGO:</b> Podrá realizar el pago de su obligación a través de los siguientes medios de
                pago dispuestos y debidamente autorizados por ABANTA SOLUCIONES FINANCIERAS S.A.S., medios que
                se encuentran al alcance del público las 24 horas en la plataforma web WWW.ABANTA.CO
            </div>
            <div class="text-center" style="margin: 10px 0">
                <p class="fs-10px fw-bold" style="color: #2AB4F0">Ingrese al Link
                    https://www.mipagoamigo.com/MPA_WebSite/ServicePayments</p>
                <p class="fs-11px fw-bold" style="color: #f00">Ingrese el nombre del Convenio ABANTA SOLUCIONES
                    FINANCIERAS y al colocar su identificacion podra acceder al pago.</p>
            </div>
        @else
            <br>
        @endif
    @endif
    <table style="width: 100%; page-break-inside: avoid;">
        <thead class="fs-11px" style="background-color: {{ $colorBase }}">
            <tr>
                <th class="text-left fw-bold" style="color: white; padding: .5rem 1rem;" colspan="3">
                    ENVÍO
                </th>
            </tr>
        </thead>
        <tbody class="fs-10px">
            <tr>
                <td class="fw-bold text-left p-envio">
                    Nombre del cliente:
                </td>
                <td class="text-left p-envio">
                    {{ $cliente['nombre'] }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    Id. del cliente:
                </td>
                <td class="text-left p-envio">
                    {{ $cliente['cedula'] }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    N.º de extracto:
                </td>
                <td class="text-left p-envio">
                    {{ $numExtracto > 0 ? $numExtracto : '' }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    Fecha de Pago:
                </td>
                <td class="text-left p-envio">
                    {{ $fechaPago ? \Carbon\Carbon::parse($fechaPago)->isoFormat('D MMM YYYY') : '' }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    Saldo a pagar del crédito:
                </td>
                <td class="text-left p-envio">
                    {{ $saldoPagar > 0 ? '$' . number_format($saldoPagar) : '' }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    Total intereses moratorios:
                </td>
                <td class="text-left p-envio">
                    {{ $totalIntMora > 0 ? '$' . number_format($totalIntMora) : '' }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    Total gastos de cobranza:
                </td>
                <td class="text-left p-envio">
                    {{ $totalGCobranza > 0 ? '$' . number_format($totalGCobranza) : '' }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    Importe a pagar:
                </td>
                <td class="text-left p-envio">
                    {{ $granTotal > 0 ? '$' . number_format($granTotal) : '' }}
                </td>
            </tr>
            <tr>
                <td class="fw-bold text-left p-envio">
                    Importe entregado al correo:
                </td>
                <td class="text-left p-envio">
                    {{ $cliente['email'] }}
                </td>
            </tr>
        </tbody>
    </table>
    @if (isset($infoPersonalizadaExtracto) && $infoPersonalizadaExtracto->observacion_2 !== null)
        <div class="fs-12px text-center">
            <p>{!! nl2br(e($infoPersonalizadaExtracto->observacion_2)) !!}</p>
        </div>
    @else
        @if ($empresa->nit == '901676539-7')
            <div>
                <p class="fs-9px text-center">
                    • La revocatoria, cancelación anticipada o retiro de la póliza contratada entre EL DEUDOR y LA
                    ASEGURADORA, no implican modificaciones en el crédito referentes al saldo del capital, la cuota
                    pactada o
                    la terminación de la obligación; toda vez que la póliza, su vigencia y coberturas no están
                    vinculadas al crédito ni al contrato de mutuo celebrado entre ABANTA y EL DEUDOR.
                </p>
            </div>
            <div>
                <p class="fs-9px text-center">
                    Desde el 1 de Enero del 2024 todos creditos gozaran de Seguro Vida Deudor , la tarifa será $500 y
                    cubrirá el saldo total de la deuda en caso de fallecimiento y/o incapacidad total permanente. Más
                    información notificaciones@abanta.co.
                </p>
            </div>
        @endif
    @endif
</body>

</html>
