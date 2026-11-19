<?php

namespace App\Notifications\Host;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;

/** Calendrier soudain vide, refusé par précaution : l'hôte confirme s'il l'est vraiment. */
class IcalEmptyCalendarRefusedNotification extends IcalFeedNotification
{
    public function type(): NotificationType
    {
        return NotificationType::IcalEmptyCalendarRefused;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Urgent;
    }

    public function title(): string
    {
        return __('notifications.ical_empty_calendar_refused.title', $this->names(), 'fr');
    }

    public function body(): string
    {
        return __('notifications.ical_empty_calendar_refused.body', $this->names(), 'fr');
    }
}
