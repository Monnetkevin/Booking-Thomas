<?php

namespace App\Notifications\Host;

use App\Models\IcalFeed;
use App\Notifications\BackOfficeNotification;

/**
 * Notification sur l'état d'un calendrier de plateforme : une seule non lue par calendrier
 * et par type, vers l'écran de synchronisation.
 */
abstract class IcalFeedNotification extends BackOfficeNotification
{
    public function __construct(public readonly IcalFeed $feed)
    {
        parent::__construct();
    }

    public function link(): string
    {
        return '/admin/synchronisation';
    }

    public function dedupeKey(): string
    {
        return 'feed:'.$this->feed->id;
    }

    /**
     * @return array{provider: string, apartment: string}
     */
    protected function names(): array
    {
        return [
            'provider' => $this->feed->provider->label(),
            'apartment' => $this->feed->apartment->getTranslation('name', 'fr'),
        ];
    }
}
