<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Mail\BookingConfirmedGuest;
use App\Mail\BookingConfirmedHost;
use App\Services\Notifications\HostNotifier;
use Illuminate\Support\Facades\Mail;

/** Confirmation au voyageur (dans sa langue) et à l'hôte, chacune avec le PDF. */
class SendBookingConfirmation
{
    public function __construct(private readonly HostNotifier $host) {}

    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;

        Mail::to($booking->guest_email, $booking->guest_name)->queue(new BookingConfirmedGuest($booking, $this->host->recipients()[0] ?? null));
        $this->host->notify(new BookingConfirmedHost($booking));
    }
}
