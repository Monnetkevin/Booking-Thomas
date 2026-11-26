<?php

namespace App\Listeners;

use App\Enums\BookingSource;
use App\Events\BookingConfirmed;
use App\Mail\BookingConfirmedGuest;
use App\Mail\BookingConfirmedHost;
use App\Services\Notifications\HostNotifier;
use Illuminate\Support\Facades\Mail;

/**
 * Confirmation au voyageur (dans sa langue) et à l'hôte, chacune avec le PDF. Une
 * réservation saisie à la main n'envoie pas de copie à l'hôte (il vient de la saisir) ;
 * le voyageur la reçoit si l'hôte le veut et que son e-mail est connu.
 */
class SendBookingConfirmation
{
    public function __construct(private readonly HostNotifier $host) {}

    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;

        if ($event->notifyGuest && filled($booking->guest_email)) {
            Mail::to($booking->guest_email, $booking->guest_name)->queue(new BookingConfirmedGuest($booking, $this->host->recipients()[0] ?? null));
        }
        if ($booking->source === BookingSource::Direct) {
            $this->host->notify(new BookingConfirmedHost($booking));
        }
    }
}
