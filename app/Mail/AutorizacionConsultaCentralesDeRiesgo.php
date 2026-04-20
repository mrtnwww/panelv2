<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AutorizacionConsultaCentralesDeRiesgo extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $firmaCliente;
    public $cliente;
    public $empresa;
    public $asunto;
    public $texto;
    public function __construct($firmaCliente, $cliente, $empresa, $url = "", $texto = '', $asunto = '')
    {
        if ($url === "credivehiculo") {
            $this->firmaCliente = url('authorizationAcceptVehiculo/' . $firmaCliente);
        }
        if ($url === "credihipoteca") {
            $this->firmaCliente = url('authorizationAcceptHipoteca/' . $firmaCliente);
        }
        if ($url === "") {
            $this->firmaCliente = url('authorizationAccept/' . $firmaCliente);
        }
        $this->cliente = $cliente;
        $this->empresa = $empresa;
        $this->asunto = $asunto;
        $this->texto = $texto;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if (!empty($this->texto)) {
            $texto = $this->texto;

            return $this
                ->from('noreply@credigitalco.com', 'SOLICITUD DE CRÉDITO')
                ->subject($this->asunto ?? 'Autorización de consulta en centrales de riesgo')
                ->view('mail.autorizacionPlantilla', compact('texto'));
        } else {
            return $this
                ->from('noreply@credigitalco.com', 'SOLICITUD DE CRÉDITO')
                ->subject('Autorización de consulta en centrales de riesgo')
                ->view('mail.autorizacion');
        }
    }
}
