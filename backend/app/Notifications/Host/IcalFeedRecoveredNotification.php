<?php

namespace App\Notifications\Host;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;

/** Calendrier de nouveau lisible après une alerte d'échec. */
class IcalFeedRecoveredNotification extends IcalFeedNotification
{
    public function type(): NotificationType
    {
        return NotificationType::IcalFeedRecovered;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Info;
    }

    public function title(): string
    {
        return __('notifications.ical_feed_recovered.title', $this->names(), 'fr');
    }

    public function body(): string
    {
        return __('notifications.ical_feed_recovered.body', $this->names(), 'fr');
    }
}
