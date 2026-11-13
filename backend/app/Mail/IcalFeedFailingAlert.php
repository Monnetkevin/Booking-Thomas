<?php

namespace App\Mail;

use App\Models\IcalFeed;
use App\Support\SiteUrl;

/** Calendrier d'une plateforme illisible plusieurs fois de suite, pour l'hôte. */
class IcalFeedFailingAlert extends TransactionalMail
{
    public function __construct(public readonly IcalFeed $feed)
    {
        $this->prepare('fr');
    }

    protected function markdownView(): string
    {
        return 'mail.ical-feed-failing';
    }

    protected function subjectLine(): string
    {
        return __('mail.ical_failing.subject', [
            'provider' => $this->feed->provider->label(),
            'apartment' => $this->feed->apartment->getTranslation('name', 'fr'),
        ]);
    }

    protected function reason(): string
    {
        return __('mail.ical_conflict.reason');
    }

    protected function viewData(): array
    {
        return [
            'provider' => $this->feed->provider->label(),
            'apartment' => $this->feed->apartment->getTranslation('name', 'fr'),
            'failures' => $this->feed->consecutive_failures,
            'error' => $this->feed->last_error,
            'lastSuccess' => $this->feed->last_succeeded_at?->locale('fr')->translatedFormat('j F Y à H:i'),
            'adminUrl' => SiteUrl::to('/admin/synchronisation'),
        ];
    }
}
