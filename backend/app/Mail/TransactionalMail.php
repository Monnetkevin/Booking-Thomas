<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Base de tous les e-mails du site. Un nouvel e-mail ne fournit que son contenu :
 * vue Markdown (qui commence par <x-mail::message>), objet, données et, au besoin,
 * adresse de réponse et raison de l'envoi affichée en pied de page.
 *
 * Communs à tous : gabarit et thème du site, langue du destinataire (fr ou en),
 * version texte, envoi en file d'attente après la validation de la transaction.
 */
abstract class TransactionalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const LOCALES = ['fr', 'en'];

    /** Vue Markdown du contenu, ex. « mail.booking-confirmed ». */
    abstract protected function markdownView(): string;

    /** Objet, traduit dans la langue de l'e-mail. */
    abstract protected function subjectLine(): string;

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [];
    }

    protected function replyToAddress(): ?Address
    {
        return null;
    }

    /** Raison de l'envoi, en pied de page ; null pour la phrase par défaut. */
    protected function reason(): ?string
    {
        return null;
    }

    /** Langue du destinataire (fr par défaut) et envoi seulement une fois la transaction validée. */
    protected function prepare(string $locale): void
    {
        $this->locale(in_array($locale, self::LOCALES, true) ? $locale : 'fr');
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $replyTo = $this->replyToAddress();

        return new Envelope(subject: $this->subjectLine(), replyTo: $replyTo ? [$replyTo] : []);
    }

    public function content(): Content
    {
        return new Content(markdown: $this->markdownView(), with: [...$this->viewData(), 'reason' => $this->reason()]);
    }

    /** Version texte, telle qu'envoyée avec la version HTML (aperçu local). */
    public function renderText(): string
    {
        return $this->withLocale($this->locale, function (): string {
            $this->prepareMailableForDelivery();
            $view = $this->buildView();
            $text = is_array($view) ? ($view['text'] ?? '') : '';

            return $text instanceof Htmlable
                ? $text->toHtml()
                : (string) Container::getInstance()->make('mailer')->render($text, $this->buildViewData());
        });
    }
}
