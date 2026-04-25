<?php
$medidaTicket = 280;

?>
<!DOCTYPE html>
<html>

<head>

    <style>
        * {
            font-size: 12px;
            font-family: 'DejaVu Sans', serif;
        }

        h1 {
            font-size: 18px;
        }

        .ticket {
            margin: 2px;
        }

        td,
        th,
        tr,
        table {
            border-top: 1px solid black;
            border-collapse: separate;
            margin: 0 auto;
            border-spacing: 2px;
        }

        td.cliente {
            text-align: center;
            font-size: 11px;
        }

        td.credito {
            text-align: center;
            font-size: 11px;
        }

        td.valor_cxc {
            text-align: center;
            font-size: 11px;

        }

        th {
            text-align: center;
        }


        .centrado {
            text-align: center;
            align-content: center;
        }

        .ticket {
            width: <?php echo $medidaTicket; ?>px;
            max-width: <?php echo $medidaTicket; ?>px;
        }

        img {
            max-width: inherit;
            width: inherit;
        }

        * {
            margin: 0;
            padding: 0;
        }

        .ticket {
            margin: 0;
            padding: 0;
        }

        body {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="ticket centrado">

        <p>Recibo de caja CXC N° {{ $consecutivoRecibo }} </p>
        <p>{{ $hoy }}</p>
        <br>
        <p>{{ $empresa->razon_social }}</p>
        <p>NIT: {{ $empresa->nit }}</p>
        <p>Fecha de creación: {{ $fechaRecibo }}</p>
        <br>
        <p> Valor total CXC: ${{ number_format($totalCXC, 0) }}</p>
        <br>

        <table>
            <thead>
                <tr class="centrado">
                    <th class="cliente">Cliente</th>
                    <th class="credito">Crédito</th>
                    <th class="valor_cxc">Valor CXC</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($creditos as $item)
                    <tr>
                        <td class="cliente">
                            {{ strtoupper($item->cliente->nombre) ?? '' }}
                        </td>
                        <td class="credito">
                            Crédito {{ $item->consecutivo }} ({{'$' . number_format($item->valor_compra, 2, ',', '.') }})
                        </td>
                        <td class="valor_cxc">
                            {{ '$' . number_format($item->valor_cxc, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
