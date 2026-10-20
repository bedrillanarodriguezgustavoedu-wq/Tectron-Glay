<?php

namespace App\Mail;

use App\Models\Orden;
use App\Models\Ajuste;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstadoPedidoActualizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $orden;
    public $estado;
    public $ajuste;

    /**
     * Create a new message instance.
     */
    public function __construct(Orden $orden, string $estado, Ajuste $ajuste)
    {
        $this->orden = $orden;
        $this->estado = $estado;
        $this->ajuste = $ajuste;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Actualización de tu pedido';
        switch ($this->estado) {
            case 'Recibido':
                $subject = 'Tu pedido ha sido recibido';
                break;
            case 'Enviado':
                $subject = 'Tu pedido ha sido enviado';
                break;
            case 'En transporte':
                $subject = 'Tu pedido se encuentra en transporte';
                break;
            case 'Listo para su recojo':
                $subject = 'Tu pedido está listo para recoger en tienda';
                break;
            case 'Entregado':
                $subject = 'Tu pedido ha sido entregado';
                break;
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.estado_pedido_actualizado',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
