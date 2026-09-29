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

/** La promotora ha revisado su visor: lo aprueba, o pide cambios. Al equipo. */
class VisorRevisado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Project $project, public User $quienRevisa, public bool $aprobado, public ?string $comentario) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->aprobado ? 'Visor aprobado: ' : 'Cambios en el visor: ').$this->project->name,
            replyTo: [new Address($this->quienRevisa->email, $this->quienRevisa->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.visor-revisado');
    }
}
