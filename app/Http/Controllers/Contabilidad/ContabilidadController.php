<?php

namespace App\Http\Controllers\Contabilidad;

use App\Http\Controllers\Controller;
use App\Models\Credito;
use App\Models\CreditoCXC;
use App\Models\Producto;
use App\Models\ReciboCajaCXC;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContabilidadController extends Controller
{
    function listRecibosCXC(Request $request)
    {
        $usuario = auth()->user();

        $empresaId = $usuario->empresa_id;

        $per_page = $request->input('per_page', 10);
        $search = $request->input('search');

        $conditions = [
            'fecha_inicial' => $request->input('fecha_inicial', ''),
            'fecha_final' => $request->input('fecha_final', ''),
            'aliado' => $request->input('establecimiento', '')
        ];

        $recibosQuery = ReciboCajaCXC::where('empresa_principal_id', $empresaId)
            ->with(['empresa', 'producto'])
            ->applySearch($search)
            ->applyConditions($conditions)
            ->orderBy('created_at', 'desc');

        $creditosId = $recibosQuery->cursor()
            ->flatMap(fn($item) => json_decode($item->id_creditos, true))
            ->toArray();

        $totalCXC = empty($creditosId) ? 0 : Credito::whereIn('id', $creditosId)->sum('valor_cxc');

        $recibosCaja = $recibosQuery->paginate($per_page);

        $recibosCaja->getCollection()->transform(function ($item) {
            $item->fecha = date('Y-m-d', strtotime($item->created_at));
            $item->valor_cxc = Credito::whereIn('id', json_decode($item->id_creditos))->sum('valor_cxc');
            return $item;
        });

        return response()->json([
            'id_empresa' => $empresaId,
            'recibosCaja' => $recibosCaja,
            'totalCXC' => $totalCXC
        ]);
    }

    public function saveRecibosCXC(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $request->validate([
            'recibosCaja' => 'required|array|min:1',
            'establecimiento' => 'required|integer',
        ]);

        $response = DB::transaction(function () use ($request, $empresaId) {
            $recibosCaja = $request->input('recibosCaja');
            $creditosAgrupados = collect($recibosCaja)->groupBy('id_credito');
            $idCreditos = array_unique($creditosAgrupados->keys()->toArray());

            // Generar el recibo de caja
            $reciboCajaCXC = new ReciboCajaCXC();
            $reciboCajaCXC->created_at = \Carbon\Carbon::now();
            $reciboCajaCXC->id_creditos = json_encode($idCreditos);
            $reciboCajaCXC->empresa_id = $request->input('establecimiento');
            $reciboCajaCXC->empresa_principal_id = $empresaId;
            $reciboCajaCXC->save();

            // Iterar los creditos y agregarlos a la tabla intermedia
            foreach ($recibosCaja as $recibo) {
                $creditoCXC = new CreditoCXC();
                $creditoCXC->credito_id = $recibo['id_credito'];
                $creditoCXC->producto_id = $recibo['productoSeleccionado']['id'];
                $creditoCXC->recibo_caja_id = $reciboCajaCXC->id;
                $creditoCXC->save();
            }

            // Actualizar la cuenta por cobrar aliado en la tabla credito
            foreach ($creditosAgrupados as $idCredito => $recibos) {
                $sumaPrecios = $recibos->sum(fn($recibo) => (int) $recibo['productoSeleccionado']['precio']);
                $productos = $recibos->pluck('productoSeleccionado.nombre')->join(';  ');

                Credito::where('id', $idCredito)
                    ->update([
                        'producto' => $productos,
                        'valor_cxc' => $sumaPrecios
                    ]);
            }

            return response()->json(
                ['message' => 'Recibo de caja generado correctamente']
            );
        });

        return $response;
    }

    public function imprimirReciboCXC(Request $request)
    {
        $hoy = Carbon::now();

        $reciboCaja = ReciboCajaCXC::where('id', $request->input('id'))
            ->with(['empresa'])
            ->first();

        $empresa = $reciboCaja->empresa;
        $fechaRecibo = Carbon::parse($reciboCaja->created_at)->format('Y-m-d');
        $idCreditos = json_decode($reciboCaja->id_creditos);

        // Valor total de la cuenta por cobrar de los creditos asociados al recibo de caja
        $totalCXC = Credito::whereIn('id', $idCreditos)->sum('valor_cxc');

        $reciboCajaCXC = CreditoCXC::where('recibo_caja_id', $reciboCaja->id)->get();
        if ($reciboCajaCXC->isNotEmpty()) {
            $creditos = $reciboCajaCXC->map(function ($recibo) {
                $credito = Credito::select('id', 'consecutivo', 'valor_compra', 'valor_cxc', 'client_id')
                    ->where('id', $recibo->credito_id)
                    ->with(['cliente'])
                    ->first();

                if ($credito) {
                    $productoPrecio = Producto::withTrashed()
                        ->where('id', $recibo->producto_id)
                        ->value('precio');
                    $credito->valor_cxc = $productoPrecio;
                }

                return $credito;
            });

            $creditos = $creditos->filter();
        } else {
            $creditos = Credito::select('id', 'consecutivo', 'valor_compra', 'valor_cxc', 'client_id')
                ->whereIn('id', $idCreditos)
                ->with(['cliente'])
                ->get();
        }

        $fileName = 'ReciboCajaCXC_' . $reciboCaja->id . '.pdf';
        $consecutivoRecibo = $reciboCaja->id;
        $pdf = \PDF::loadView('pdf.reciboCajaCXC', compact('hoy', 'empresa', 'fechaRecibo', 'totalCXC', 'creditos', 'consecutivoRecibo'))->setPaper([0, 0, 220, 1000]);

        Storage::disk('s3')->put($fileName, $pdf->output());
        Storage::disk('s3')->url($fileName);

        $expiracion = Carbon::now()->addMinutes(30); // Establecer la expiración en 5 minutos
        $url = Storage::disk('s3')->temporaryUrl($fileName, $expiracion);

        return response()->json([
            'message' => 'PDF recibo de caja CXC generado correctamente',
            'url' => $url
        ]);
    }
}
