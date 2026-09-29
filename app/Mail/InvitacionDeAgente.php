<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** La promotora invita a un agente: el enlace para elegir contraseña y entrar. */
class InvitacionDeAgente extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $agente, public User $promotora) {}

    public function empresa(): string
    {
        return $this->promotora->companyProfile?->company_name ?? $this->promotora->name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('agentes.asunto', ['empresa' => $this->empresa()]),
            replyTo: [new Address($this->promotora->email, $this->promotora->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.invitacion-de-agente', with: [
            'empresa' => $this->empresa(),
            'enlace' => route('invitacion.mostrar', $this->agente->invitacion_token),
        ]);
    }
}
