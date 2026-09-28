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
        // markdown, no view: la plantilla usa componentes <x-mail::message>, y
        // con `view` Laravel no registra el espacio de nombres "mail" y revienta
        // al renderizar. No se veia en los tests porque Mail::fake() no pinta la
        // plantilla; lo cazo el recorrido nocturno al mandar el correo de verdad.
        return new Content(markdown: 'emails.solicitud-de-visor');
    }
}
