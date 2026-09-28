<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewInquiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva consulta: '.$this->inquiry->project->name.' - '.$this->inquiry->name,
            // Para que la promotora pueda darle a Responder y escribirle al
            // comprador directamente. Sin esto contestaba a noreply@real3d.io,
            // que es tirar el lead a la basura con un clic.
            replyTo: [new Address($this->inquiry->email, $this->inquiry->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-inquiry',
            with: [
                'inquiry' => $this->inquiry,
                'project' => $this->inquiry->project,
                'unit' => $this->inquiry->unit,
            ],
        );
    }
}
