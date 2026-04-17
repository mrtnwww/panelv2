<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FirmaDocumentos extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $urlDocuments;
    public $empresa;
    public $cliente;
    public $documentos;


    public function __construct($urlDocuments, $empresa, $cliente, $documentos)
    {
        $this->urlDocuments = $urlDocuments;
        $this->empresa = $empresa;
        $this->cliente = $cliente;
        $this->documentos = $documentos;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from('noreply@credigitalco.com', 'SOLICITUD DE CRÉDITO')
            ->subject('Firma de documentos')
            ->view('mail.firma_documentos');
    }
}

