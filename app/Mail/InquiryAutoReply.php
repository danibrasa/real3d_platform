<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        $contacto = $this->inquiry->project->contact_email
            ?: $this->inquiry->project->creator?->email;

        return new Envelope(
            subject: 'Recibimos tu consulta - '.$this->inquiry->project->name,
            // Si el comprador responde a este acuse, que llegue a alguien. Un
            // "gracias, ¿cuando puedo visitarlo?" contestado a noreply no lo
            // lee nadie.
            replyTo: $contacto ? [new Address($contacto)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiry-auto-reply',
            with: [
                'inquiry' => $this->inquiry,
                'project' => $this->inquiry->project,
                'unit' => $this->inquiry->unit,
            ],
        );
    }
}
