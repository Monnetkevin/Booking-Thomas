<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Support\Money;

/**
 * Récapitulatif d'une réservation dans une langue donnée, partagé par les e-mails de
 * confirmation et le PDF : mêmes libellés, mêmes montants, mêmes dates.
 */
class BookingSummary
{
    public function __construct(private readonly BookingLinks $links) {}

    /**
     * @return array{
     *     reference: string,
     *     apartment: string,
     *     address: string,
     *     dates: string,
     *     arrival: string,
     *     departure: string,
     *     nights: int,
     *     guests: string,
     *     lines: list<array{0: string, 1: string}>,
     *     total: string,
     *     tiers: list<string>,
     *     tracking_url: string,
     * }
     */
    public function for(Booking $booking, string $locale): array
    {
        $apartment = $booking->apartment;
        $format = fn ($date) => $date->locale($locale)->translatedFormat('D j M Y');
        $guests = trans_choice('mail.booking.adults', $booking->adults, ['count' => $booking->adults], $locale);
        if ($booking->children > 0) {
            $guests .= ', '.trans_choice('mail.booking.children', $booking->children, ['count' => $booking->children], $locale);
        }

        /** @var list<array{label: string, amount: int}> $lines */
        $lines = $booking->price_breakdown['lines'] ?? [];
        $tiers = array_map(
            fn (array $tier) => $tier['days_before'] > 0
                ? __('mail.booking.tier', ['percent' => $tier['refund_percent'], 'days' => $tier['days_before']], $locale)
                : __('mail.booking.tier_last', ['percent' => $tier['refund_percent']], $locale),
            $booking->cancellation_policy['tiers'] ?? [],
        );

        return [
            'reference' => $booking->reference,
            'apartment' => $apartment->getTranslation('name', $locale),
            'address' => "{$apartment->address}, {$apartment->postal_code} {$apartment->city}",
            'dates' => $format($booking->arrival).' → '.$format($booking->departure),
            'arrival' => __('mail.booking.arrival_value', ['date' => $format($booking->arrival), 'time' => substr((string) $apartment->check_in_time, 0, 5)], $locale),
            'departure' => __('mail.booking.departure_value', ['date' => $format($booking->departure), 'time' => substr((string) $apartment->check_out_time, 0, 5)], $locale),
            'nights' => $booking->nights(),
            'guests' => $guests,
            'lines' => array_map(fn (array $line) => [$line['label'], Money::format($line['amount'], $locale)], $lines),
            'total' => Money::format($booking->total_amount, $locale),
            'tiers' => $tiers,
            'tracking_url' => $this->links->trackingUrl($booking),
        ];
    }
}
