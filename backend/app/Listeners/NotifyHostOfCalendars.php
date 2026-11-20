<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\IcalConflictDetected;
use App\Events\IcalEmptyCalendarRefused;
use App\Events\IcalFeedFailing;
use App\Events\IcalFeedRecovered;
use App\Mail\IcalConflictAlert;
use App\Mail\IcalFeedFailingAlert;
use App\Models\User;
use App\Notifications\Host\IcalConflictNotification;
use App\Notifications\Host\IcalEmptyCalendarRefusedNotification;
use App\Notifications\Host\IcalFeedFailingNotification;
use App\Notifications\Host\IcalFeedRecoveredNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Notifications\HostNotifier;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Contracts\Mail\Mailable;

/**
 * Incidents des calendriers des plateformes, signalés à l'hôte : notification (back-office
 * et e-mail) à chaque administrateur actif. Le conflit de dates et le calendrier en échec
 * partent aussi aux adresses du réglage « e-mails de notification », sans doublon.
 */
class NotifyHostOfCalendars
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly HostNotifier $host,
        private readonly UserRepositoryInterface $users,
    ) {}

    /** Deux voyageurs sur les mêmes nuits : l'hôte annule l'une des réservations sans attendre. */
    public function handleConflict(IcalConflictDetected $event): void
    {
        $this->notifications->dispatch(new IcalConflictNotification($event->block, $event->bookings));
        $this->alsoToSettingAddresses(new IcalConflictAlert($event->block, $event->bookings));
    }

    /** L'hôte vérifie l'URL d'import ou synchronise à la main. */
    public function handleFailing(IcalFeedFailing $event): void
    {
        $this->notifications->dispatch(new IcalFeedFailingNotification($event->feed));
        $this->alsoToSettingAddresses(new IcalFeedFailingAlert($event->feed));
    }

    public function handleEmptyRefused(IcalEmptyCalendarRefused $event): void
    {
        $this->notifications->dispatch(new IcalEmptyCalendarRefusedNotification($event->feed));
    }

    public function handleRecovered(IcalFeedRecovered $event): void
    {
        $this->notifications->dispatch(new IcalFeedRecoveredNotification($event->feed));
    }

    /** Adresses du réglage qui ne sont pas déjà celles d'un administrateur ou gestionnaire actif. */
    private function alsoToSettingAddresses(Mailable $mail): void
    {
        $admins = $this->users->activeByRoles(UserRole::Admin->members())->map(fn (User $user) => $user->email)->values()->all();
        $this->host->notify($mail, except: $admins);
    }
}
