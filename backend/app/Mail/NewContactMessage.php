<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Prévient l'hôte d'un nouveau message ; « Répondre » écrit directement au visiteur. */
class NewContactMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ContactMessage $contact)
    {
        $this->locale('fr');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contact->email, $this->contact->name)],
            subject: "Nouveau message de {$this->contact->name}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact-message', with: [
            'adminUrl' => rtrim((string) config('booking.frontend_url'), '/').'/admin',
        ]);
    }
}
