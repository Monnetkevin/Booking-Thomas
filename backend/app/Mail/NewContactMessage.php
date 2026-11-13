<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailables\Address;

/** Prévient l'hôte d'un nouveau message ; « Répondre » écrit directement au visiteur. */
class NewContactMessage extends TransactionalMail
{
    public function __construct(public readonly ContactMessage $contact)
    {
        // E-mail destiné à l'hôte : toujours en français.
        $this->prepare('fr');
    }

    protected function markdownView(): string
    {
        return 'mail.contact-message';
    }

    protected function subjectLine(): string
    {
        return __('mail.contact.subject', ['name' => $this->contact->name]);
    }

    protected function replyToAddress(): Address
    {
        return new Address($this->contact->email, $this->contact->name);
    }

    protected function reason(): string
    {
        return __('mail.contact.reason');
    }

    protected function viewData(): array
    {
        return ['adminUrl' => rtrim((string) config('booking.frontend_url'), '/').'/admin'];
    }
}
