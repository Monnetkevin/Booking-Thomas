<?php

namespace App\Notifications\Host;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Mail\IcalFeedFailingAlert;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;

/** Calendrier illisible plusieurs fois de suite : ses nuits ne sont plus à jour. */
class IcalFeedFailingNotification extends IcalFeedNotification
{
    public function type(): NotificationType
    {
        return NotificationType::IcalFeedFailing;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Urgent;
    }

    public function title(): string
    {
        return __('notifications.ical_feed_failing.title', $this->names(), 'fr');
    }

    public function body(): string
    {
        return __('notifications.ical_feed_failing.body', [
            ...$this->names(),
            'failures' => $this->feed->consecutive_failures,
            'error' => (string) $this->feed->last_error,
        ], 'fr');
    }

    public function toMail(User $notifiable): Mailable
    {
        return (new IcalFeedFailingAlert($this->feed))->to($notifiable->email, $notifiable->name);
    }
}
