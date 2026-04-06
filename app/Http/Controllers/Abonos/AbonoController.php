<?php

namespace App\Http\Controllers\Abonos;

use App\Exports\FacturasExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\MobileController;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Condonacion;
use App\Models\Empresa;
use App\Models\ParametrosEstadoFunciones;
use App\Models\Usuario;
use App\Models\UsuarioTipoUsuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AbonoController extends Controller
{
    public function listAbonos(Request $request) {
        $userId = auth()->user()->id;
        $empresaId = auth()->user()->empresa_id;

        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');

        $generarInforme = request('generarInforme', 0);

        // Filtros
        $conditions = $request->input('conditions', []);

        $factura = 0;
        if (isset($conditions['factura'])) $factura = $conditions['factura'];

        $isAdmin = UsuarioTipoUsuario::where('id_usuario', $userId)
            ->where('id_tipo_usuario', 2)
            ->exists();

        $informeFacturas = false;

        // Se le restringe el acceso de anulacion de creditos al ing. Victor
        if (in_array($userId, [5859, 6163, 6309])) $isAdmin = false;

        // Obtener usuarios y sus abonos
        $listaSedesAliados = Empresa::where('aliado', $empresaId)
            ->orWhere('sede', $empresaId)
            ->pluck('id')
            ->push($empresaId);

        $usuarios = Usuario::whereIn('empresa_id', $listaSedesAliados)
            ->where('subtipousuario_id', '!=', 7)
            ->withTrashed()
            ->pluck('id');

        $abonosQuery = Abono::whereIn('user_id', $usuarios)
            ->where('credito_id', '>', 0)
            ->whereHas('credito', function ($query) {
                $query->whereHas('cliente');
            })
            ->with(['credito.cliente', 'tipoPago'])
            ->applyConditions($conditions)
            ->applySearch($searchTerm)
            ->orderBy('created_at', 'desc');

        // Si se hace la consulta desde informe facturas, se filtran los abonos por el campo abono_factura
        if ($factura == 1) {
            $abonosQuery->where('abono_factura', 1);
            $informeFacturas = true;
        }

        // Suma total de los abonos realizados
        $totalesQuery = (clone $abonosQuery)
            ->select(
                DB::raw('COALESCE(SUM(abono_iva_gas_cobranza),0) as totalIvaGasCobranza'),
                DB::raw('COALESCE(SUM(abono_gas_cobranza),0) as totalGasCobranza'),
                DB::raw('COALESCE(SUM(abono_int_mora),0) as totalIntMora'),
                DB::raw('COALESCE(SUM(abono_firma_elec),0) as totalFirmaElec'),
                DB::raw('COALESCE(SUM(abono_intereses),0) as totalIntereses'),
                DB::raw('COALESCE(SUM(abono_iva_aval),0) as totalIvaAval'),
                DB::raw('COALESCE(SUM(abono_capital),0) as totalCapital'),
                DB::raw('COALESCE(SUM(abono_aval),0) as totalAval'),
                DB::raw('COALESCE(SUM(valor),0) as totalAbonado')
            )
            ->first();

        $totalIvaGasCobranza = (float) $totalesQuery->totalIvaGasCobranza;
        $totalGasCobranza = (float) $totalesQuery->totalGasCobranza;
        $totalIntMora     = (float) $totalesQuery->totalIntMora;
        $totalFirmaElec   = (float) $totalesQuery->totalFirmaElec;
        $totalIntereses   = (float) $totalesQuery->totalIntereses;
        $totalIvaAval     = (float) $totalesQuery->totalIvaAval;
        $totalCapital     = (float) $totalesQuery->totalCapital;
        $totalAval        = (float) $totalesQuery->totalAval;
        $totalAbonado     = (float) $totalesQuery->totalAbonado;
        $vTotalAbonado    = (float) $totalesQuery->totalAbonado;

        $totalAbonado += ($totalIntMora ?? 0) + ($totalGasCobranza ?? 0);

        // total de abonos facturados
        $totalFacturados = (clone $abonosQuery)->where('abono_factura', 1)->count();

        if ($generarInforme) {
            $abonos = $abonosQuery->get()->transform(fn($abono) => $this->transformarAbono($abono, $isAdmin, [], $empresaId, $informeFacturas));

            $hoy = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
            $nombreArchivo = 'informe_detallado_abonos_' . $hoy . '.xlsx';
            $excel::store(new FacturasExport($abonos, $totalAbonado), $nombreArchivo, 's3');
            $fileUrl = Storage::disk('s3')->url($nombreArchivo);
            $expiracion = \Carbon\Carbon::now()->addMinutes(30); // Establecer la expiración en 5 minutos
            $fileUrl = Storage::disk('s3')->temporaryUrl($nombreArchivo, $expiracion);

            return response()->json([
                'fileUrl' => $fileUrl
            ]);
        } else {
            $abonos = $abonosQuery->paginate($perPage);

            // clientes con abonos facturados
            $clientesFactura = Cliente::whereHas('credito.abonos', function ($q) use ($listaSedesAliados) {
                $q->where('abono_factura', 1)
                    ->whereIn('empresa_id', $listaSedesAliados);
            })->distinct()->pluck('id')->toArray();

            $abonos->getCollection()->transform(fn($abono) => $this->transformarAbono($abono, $isAdmin, $clientesFactura, $empresaId, $informeFacturas));

            $mostrarTotalAbonos = ParametrosEstadoFunciones::where('empresa_id', $empresaId)
                ->whereHas('estado_funcion', function($query) {
                    $query->where('nombre_funcion', 'Mostrar totales abonos');
                })
                ->exists();

            return response()->json([
                'abonos' => $abonos,
                'total_abonos' => $totalAbonado,
                'vTotal_abonos' => $vTotalAbonado,
                'totalIntMora' => $totalIntMora,
                'totalGasCobranza' => $totalGasCobranza,
                'totalIntereses' => $totalIntereses,
                'totalFirmaElec' => $totalFirmaElec,
                'totalCapital' => $totalCapital,
                'totalIvaAval' => $totalIvaAval,
                'totalAval' => $totalAval,
                'totalIvaGasCobranza' => $totalIvaGasCobranza,
                'id_empresa' => $empresaId,
                'isAdmin' => $isAdmin,
                'totalFacturados' => $totalFacturados ?? 0,
                'mostrarTotalAbonos' => $mostrarTotalAbonos
            ]);
        }
    }

    private function transformarAbono($abono, $isAdmin, $clientesFactura = [], $userEmpresa, $informeFacturas = false) {
        $cliente = $abono->credito->cliente;
        $miEmpresa = optional(optional($abono->credito)->empresa) ?? null;

        //valores iniciales
        $aval = 0;
        $capital = 0;
        $ivaAval = 0;
        $intereses = 0;
        $firmaElec = 0;

        if ($abono->abono_concepto_capital == 0) {
            if (
                is_null($abono->abono_intereses) ||
                is_null($abono->abono_firma_elec) ||
                is_null($abono->abono_capital) ||
                is_null($abono->abono_aval) ||
                is_null($abono->abono_iva_aval)
            ) {
                // Calcular intereses, ava, iva del aval, firma electronica y capital cubiertos por el abono
                $abonosAsociados = $this->procesarAbonos($abono, true);

                $ultimoAbono = end($abonosAsociados) ?: [];
                $aval = $ultimoAbono['detalles']['aval'] ?? 0;
                $capital = $ultimoAbono['detalles']['capital'] ?? 0;
                $ivaAval = $ultimoAbono['detalles']['avalIva'] ?? 0;
                $intereses = $ultimoAbono['detalles']['intereses'] ?? 0;
                $firmaElec = $ultimoAbono['detalles']['firmaElec'] ?? 0;

                // Actualizar la informacion del abono en base de datos
                $actualizarAbono = Abono::find($abono->id);
                $actualizarAbono->abono_capital = $capital;
                $actualizarAbono->abono_aval = $aval;
                $actualizarAbono->abono_iva_aval = $ivaAval;
                $actualizarAbono->abono_intereses = $intereses;
                $actualizarAbono->abono_firma_elec = $firmaElec;
                $actualizarAbono->save();
            } else {
                $aval = $abono->abono_aval ?? 0;
                $capital = $abono->abono_capital ?? 0;
                $ivaAval = $abono->abono_iva_aval ?? 0;
                $intereses = $abono->abono_intereses ?? 0;
                $firmaElec = $abono->abono_firma_elec ?? 0;
            }
        } else {
            $capital = $abono->abono_capital ?? 0;
        }

        // Valor condonación crédito
        $condonacionesCredito = Condonacion::where('abono_id', $abono->id)
            ->where('concepto_condonacion', 'credito')
            ->sum('valor_condonado');

        // consultar si el cliente ya se ha registrado en el sw de facturacion electronica
        if (!$cliente->clienteFe && $informeFacturas) {
            $feService = new FacturacionElectronicaService();

            $fe = $feService->client($userEmpresa);

            if ($fe) {
                $baseUrl = $fe['baseUrl'];
                $http = $fe['http'];

                $creacionTerceros = $http->get($baseUrl . 'ClientesFind?identificacion=' . $cliente->cedula);
                $data = $creacionTerceros->json();

                if (!empty($data)) {
                    $idCliente = $data[0]['IdCliente'] ?? null;

                    if ($idCliente) {
                        $cliente->clienteFE = true;
                        $cliente->clienteFE_id = $idCliente;
                        $cliente->save();
                    }
                }
            }
        }

        // fecha de facturacion abono ante la DIAN
        $fechaFacturaFE = $abono->fecha_factura_fe
            ? Carbon::parse($abono->fecha_factura_fe)->subHours(5)->format('Y-m-d H:i:s')
            : null;

        return [
            "abono_id" => $abono->id,
            "is_admin" => $isAdmin,
            "anulado" => (bool)$abono->deleted_at,
            "fecha" => Carbon::parse($abono->created_at)->subHours(5)->format('Y-m-d H:i:s'),
            "cedula" => $cliente->cedula ?? '',
            "client_id" => $cliente->id ?? '',
            "name" => $cliente->nombre ?? '',
            "numcredito" => $abono->credito->id,
            "numabono" => $abono->id,
            "abones" => $abono->valor,
            "interest" => 0,
            "concept" => $abono->observaciones ?? '',
            "person" => $abono->credito->user_id,
            "placa" => '',
            "payed" => $abono->tipoPago->nombre ?? '',
            'empresa' => $miEmpresa->razon_social ?? '',
            "gas_cobranza" => $abono->abono_gas_cobranza ?? 0,
            "iva_gas_cobranza" => $abono->abono_iva_gas_cobranza ?? 0,
            "int_mora" => $abono->abono_int_mora ?? 0,
            "capital" => $capital,
            "aval" => $aval,
            "iva_aval" => $ivaAval,
            "intereses" => $intereses,
            "firmaElectronica" => $firmaElec,
            "diasMora" => $abono->dias_mora ?? 0,
            "casa_gas_cobranza" => $abono->casa_gas_cobranza ?? 0,
            "cajera" => $abono->user->persona->nombre ?? '',
            "valorCondonacion" => $condonacionesCredito ?? 0,
            "abonoFactura" => $abono->abono_factura ?? 0,
            "clienteFacturado" => in_array($cliente->id, $clientesFactura),
            "clienteFE" => $cliente->clienteFE,
            "clienteFE_id" => $cliente->clienteFE_id,
            "facturaFE" => $fechaFacturaFE,
            "CUFE" => $abono->cufe_factura_fe ?? ''
        ];
    }

    function procesarAbonos($abono, $calculoAbono = null) {
        // $planPagos = (new MobileController)->obtenerPlanDePagos($abono->credito_id);
        $planPagos = app(MobileController::class)->obtenerPlanDePagos($abono->credito_id, $calculoAbono);
        array_shift($planPagos); // Se elimina el primer elemento cuyos valores se encuentran en 0
        // Se consultan los abonos realizados desde el mas antiguo al mas reciente
        $abonosCredito = Abono::where('credito_id', $abono->credito_id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'valor']);

        // Se itera el plan de pagos y los abonos realizados para determinar los detalles de cada abono
        $indicePlan = 0;
        $abonosAsociados = [];

        foreach ($abonosCredito as $a) {
            $valorPendiente = $a->valor; // Valor del abono actual (Se itera tanto el abono actual como los abonos anteriores)
            $detalleAbono = [ 'intereses' => 0, 'firmaElec' => 0, 'aval' => 0, 'avalIva' => 0, 'capital' => 0, 'otros' => 0 ];

            while ($indicePlan < count($planPagos)) {
                $p = &$planPagos[$indicePlan];

                if ($valorPendiente <= 0) break;

                // Se cubren los intereses pendientes
                if ($p['intereses'] > 0) {
                    $aCubrir = min($valorPendiente, $p['intereses']);
                    $detalleAbono['intereses'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['intereses'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre la firma electronica pendiente
                if ($valorPendiente > 0 && $p['firmaElec'] > 0) {
                    $aCubrir = min($valorPendiente, $p['firmaElec']);
                    $detalleAbono['firmaElec'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['firmaElec'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el aval pendiente
                if ($valorPendiente > 0 && $p['aval'] > 0) {
                    $aCubrir = min($valorPendiente, $p['aval']);
                    $detalleAbono['aval'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['aval'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el iva del aval pendiente
                if ($valorPendiente > 0 && $p['avalIva'] > 0) {
                    $aCubrir = min($valorPendiente, $p['avalIva']);
                    $detalleAbono['avalIva'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['avalIva'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el valor de otros gastos pendientes
                if ($valorPendiente > 0 && $p['otros'] > 0) {
                    $aCubrir = min($valorPendiente, $p['otros']);
                    $detalleAbono['otros'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['otros'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Se cubre el capital pendiente
                if ($valorPendiente > 0 && $p['capital'] > 0) {
                    $aCubrir = min($valorPendiente, $p['capital']);
                    $detalleAbono['capital'] += $aCubrir;
                    $valorPendiente -= $aCubrir;
                    $p['capital'] -= $aCubrir; // Se actualiza el valor pendiente
                }

                // Si el abono cubrio la cuota se avanza a la siguiente
                if ($p['intereses'] <= 0 && $p['firmaElec'] <= 0 && $p['capital'] <= 0) {
                    $indicePlan++;
                } else {
                    break;
                }
            }

            $abonosAsociados[] = [
                'valorAbono' => $a->valor,
                'detalles' => $detalleAbono,
            ];

            if ($a->id == $abono->id) break;
        }

        return $abonosAsociados;
    }
}
