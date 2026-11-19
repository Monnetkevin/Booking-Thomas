<?php

namespace App\Mail;

use App\Support\SiteUrl;

/**
 * Notification du back-office envoyée par e-mail (hôte ou ménage) : titre, texte, détails
 * éventuels et bouton vers la page concernée. Toujours en français, comme le back-office.
 */
class NotificationMail extends TransactionalMail
{
    /**
     * @param  list<string>  $details
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $details = [],
        public readonly ?string $link = null,
    ) {
        $this->prepare('fr');
    }

    protected function markdownView(): string
    {
        return 'mail.notification';
    }

    protected function subjectLine(): string
    {
        return $this->title;
    }

    protected function reason(): string
    {
        return __('mail.notification.reason');
    }

    protected function viewData(): array
    {
        return ['adminUrl' => SiteUrl::to($this->link ?? '/admin/notifications')];
    }
}
