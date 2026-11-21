<?php

namespace App\Services\Booking;

use App\Enums\AuditAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Historique d'une réservation pour sa fiche : création, paiements et remboursements,
 * confirmation, corrections et annulation (journal d'audit, avec leur auteur), puis
 * l'arrivée et le départ à venir.
 */
class BookingHistory
{
    public function __construct(private readonly AuditLogRepositoryInterface $logs) {}

    /**
     * @return list<array{event: string, at: string, by: string|null, amount: int|null, upcoming: bool}>
     */
    public function of(Booking $booking, CarbonImmutable $today): array
    {
        $events = [];
        $add = function (string $event, ?\DateTimeInterface $at, ?string $by = null, ?int $amount = null, bool $upcoming = false) use (&$events): void {
            if ($at !== null) {
                $events[] = ['event' => $event, 'at' => CarbonImmutable::instance($at)->toIso8601String(), 'by' => $by, 'amount' => $amount, 'upcoming' => $upcoming];
            }
        };

        $logs = $this->logs->forSubject(class_basename($booking), (string) $booking->id);
        $creation = $logs->first(fn (AuditLog $log) => $log->action === AuditAction::BookingCreated);
        $add('created', $booking->created_at, $creation?->user?->name);

        /** @var Payment $payment */
        foreach ($booking->payments as $payment) {
            $event = match (true) {
                $payment->status === PaymentStatus::Failed => 'payment_failed',
                $payment->status !== PaymentStatus::Succeeded => null,
                $payment->type === PaymentType::Refund => 'refunded',
                default => 'paid',
            };
            if ($event !== null) {
                $add($event, $payment->created_at, null, $payment->amount);
            }
        }

        // Une réservation saisie à la main est confirmée à sa création : pas de doublon.
        if ($booking->confirmed_at !== null && $booking->created_at !== null && $booking->confirmed_at->diffInSeconds($booking->created_at, true) > 5) {
            $add('confirmed', $booking->confirmed_at);
        }

        foreach ($logs as $log) {
            match ($log->action) {
                AuditAction::BookingUpdated => $add('updated', $log->created_at, $log->user?->name),
                AuditAction::BookingCancelled => $add('cancelled', $log->created_at, $log->user?->name),
                default => null,
            };
        }
        if ($booking->cancelled_at !== null && ! $logs->contains(fn (AuditLog $log) => $log->action === AuditAction::BookingCancelled)) {
            $add('cancelled', $booking->cancelled_at);
        }

        usort($events, fn (array $a, array $b) => $a['at'] <=> $b['at']);

        if (in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)) {
            $add('arrival', $booking->arrival, upcoming: $booking->arrival->gt($today));
            $add('departure', $booking->departure, upcoming: $booking->departure->gt($today));
        }

        return $events;
    }
}
