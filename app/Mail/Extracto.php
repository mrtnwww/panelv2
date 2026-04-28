<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Extracto extends Mailable
{
    use Queueable, SerializesModels;

    public $cuerpo;
    public $url;
    public $asunto;
    public $archivo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($cuerpo, $url, $asunto, $fileName)
    {
        //
        $this->cuerpo = $cuerpo;
        $this->url = $url;
        $this->asunto = $asunto;
        $this->archivo = $fileName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->view('view.name');
        return $this->subject($this->asunto)
            ->view('mail.extracto')
            ->attach($this->url, [
                'as' => $this->archivo,
                'mime' => 'application/pdf',
            ])
            ->with(['cuerpo' => $this->cuerpo]);
    }
}
