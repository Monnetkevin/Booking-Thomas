<?php

namespace App\Services\Booking;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\CarbonImmutable;

/**
 * Montant à rembourser si l'on annule aujourd'hui, d'après le barème copié dans la
 * réservation à sa création : premier palier dont le délai est atteint (« jusqu'à N jours
 * avant l'arrivée → X % »). Séjour commencé : rien selon le barème.
 */
class RefundCalculator
{
    public function propose(Booking $booking, CarbonImmutable $today): RefundProposal
    {
        $days = (int) $today->startOfDay()->diffInDays($booking->arrival, false);
        $tiers = $booking->cancellation_policy['tiers'];
        usort($tiers, fn (array $a, array $b) => $b['days_before'] <=> $a['days_before']);

        $percent = 0;
        if ($days >= 0) {
            foreach ($tiers as $tier) {
                if ($days >= $tier['days_before']) {
                    $percent = $tier['refund_percent'];
                    break;
                }
            }
        }

        $stayPart = max(0, $booking->accommodation_amount + $booking->extras_amount - $booking->discount_amount);
        // Une réduction plus grande que l'hébergement et les options porte sur le ménage : cette
        // part, jamais payée, n'est pas rendue.
        $cleaning = max(0, $booking->cleaning_fee - max(0, $booking->discount_amount - $booking->accommodation_amount - $booking->extras_amount));
        $byPolicy = $days >= 0
            ? (int) round($stayPart * $percent / 100) + $cleaning + $booking->tourist_tax
            : 0;

        return new RefundProposal($days, $percent, $byPolicy, $this->paid($booking));
    }

    /** Encaissé : paiements réussis moins remboursements réussis. */
    private function paid(Booking $booking): int
    {
        return (int) $booking->payments
            ->filter(fn (Payment $payment) => $payment->status === PaymentStatus::Succeeded)
            ->sum(fn (Payment $payment) => $payment->type === PaymentType::Refund ? -$payment->amount : $payment->amount);
    }
}
