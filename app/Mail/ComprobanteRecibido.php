<?php

namespace App\Mail;

use App\Models\Ajuste;
use App\Models\Orden;
use App\Models\PagoManual;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComprobanteRecibido extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Orden $orden,
        public PagoManual $pago,
        public string $urlRevision,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Revisar abono: '.$this->metodo().' del pedido #'
                .$this->orden->id.' por '.Ajuste::formatear_precio($this->orden->total),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.comprobante_recibido',
        );
    }

    public function metodo(): string
    {
        return ucfirst((string) ($this->pago->metodo_pago ?: $this->orden->metodo_pago));
    }
}
