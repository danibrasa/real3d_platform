<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** El segundo aviso: los leads que siguen sin contestar. */
class LeadsSinAtender extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $leads, public int $horas) {}

    public function envelope(): Envelope
    {
        $n = $this->leads->count();

        return new Envelope(
            subject: $n === 1
                ? 'Un comprador lleva un dia esperando respuesta: '.$this->leads->first()->name
                : "{$n} compradores llevan un dia esperando respuesta",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leads-sin-atender',
            with: ['leads' => $this->leads, 'horas' => $this->horas],
        );
    }
}
