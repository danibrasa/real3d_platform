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

/** Aviso al equipo: una promotora ha terminado su parte y espera el visor. */
class SolicitudDeVisor extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Project $project, public User $quienLoPide) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Visor pendiente: '.$this->project->name,
            // Para poder contestarle directamente y preguntarle por los planos.
            replyTo: [new Address($this->quienLoPide->email, $this->quienLoPide->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.solicitud-de-visor');
    }
}
