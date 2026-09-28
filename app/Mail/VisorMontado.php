<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso a la promotora: su visor ya esta montado y puede publicar. */
class VisorMontado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Project $project, public User $quienLoMonto) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('visor.correo_montado_asunto').': '.$this->project->name,
            // Que pueda contestar con dudas a quien lo ha montado.
            replyTo: [new Address($this->quienLoMonto->email, $this->quienLoMonto->name)],
        );
    }

    public function content(): Content
    {
        // markdown, no view: la plantilla usa componentes <x-mail::message>.
        return new Content(markdown: 'emails.visor-montado');
    }
}
