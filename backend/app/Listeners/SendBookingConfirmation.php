<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Mail\BookingConfirmedGuest;
use App\Mail\BookingConfirmedHost;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\Mail;

/** Confirmation au voyageur (dans sa langue) et à l'hôte, chacune avec le PDF. */
class SendBookingConfirmation
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $hosts = array_values(array_filter((array) $this->settings->get('notifications.admin_emails', [])));

        Mail::to($booking->guest_email, $booking->guest_name)->queue(new BookingConfirmedGuest($booking, $hosts[0] ?? null));

        if ($hosts !== []) {
            Mail::to($hosts)->queue(new BookingConfirmedHost($booking));
        }
    }
}
