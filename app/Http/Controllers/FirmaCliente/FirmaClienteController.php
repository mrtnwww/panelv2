<?php

namespace App\Http\Controllers\FirmaCliente;

use App\Http\Controllers\Controller;
use App\Mail\FirmaDocumentos;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\FirmaCliente;
use App\Models\FirmaClienteDocumento;
use App\Utils\Encryptation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class FirmaClienteController extends Controller
{
    public static function crear($clienteId, $empresaId, $documentosSend)
    {
        $firmaCliente = null;
        try {
            DB::beginTransaction();

            $cliente = Cliente::where('id', $clienteId)->first();
            $firmaCliente = FirmaCliente::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId
            ]);
            $empresa = Empresa::where('id', $empresaId)->first();
            $documentos = collect();
            foreach ($documentosSend as $documento) {
                $array = [];
                $array['firma_cliente_id'] = $firmaCliente->id;

                $document = Documento::find($documento);
                $customDocument = Documento::where('empresa_id', $empresaId)
                    ->where('key', $document->key)
                    ->first();

                if ($customDocument) {
                    $array['documento_id'] = $customDocument->id;
                } else {
                    $array['documento_id'] = $documento;
                }

                $documentos->push(FirmaClienteDocumento::create($array));
            }
            $url = url('new-credit/sign-documents?token='.Encryptation::encrypt($firmaCliente->id));
            DB::commit();

            try {
                Mail::to($cliente->email)->send(new FirmaDocumentos(
                    $url,
                    $empresa,
                    $cliente,
                    $documentos
                ));
            } catch (\Throwable $th) {
            }

            return $firmaCliente;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
}
