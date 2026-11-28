<?php

namespace App\Services\Privacy;

use App\Enums\AuditAction;
use App\Enums\BookingStatus;
use App\Exceptions\Booking\GuestStayInProgressException;
use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\ContactMessage;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\CatalogCache;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Droits RGPD des voyageurs (spec, section RGPD) :
 * - export de toutes les données liées à l'e-mail d'une réservation ;
 * - anonymisation à la demande, refusée tant qu'un séjour n'est pas terminé ;
 * - anonymisation automatique 3 ans après le départ (tâche mensuelle).
 *
 * Anonymiser efface nom, e-mail, téléphone, message, notes et messages de contact ; dates,
 * logement, montants et pays restent pour la comptabilité et les statistiques. Les avis
 * publiés restent, sans le nom affiché.
 */
class GuestPrivacyService
{
    /** Délai après le départ avant l'anonymisation automatique (décision du 2026-11-28). */
    public const KEEP_YEARS = 3;

    public const PLACEHOLDER_NAME = 'Voyageur anonymisé';

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly ContactMessageRepositoryInterface $messages,
        private readonly ReviewRepositoryInterface $reviews,
        private readonly CouponRepositoryInterface $coupons,
        private readonly AuditLogger $audit,
        private readonly CatalogCache $catalog,
    ) {}

    /**
     * Données du voyageur de cette réservation, prêtes à être remises (JSON).
     *
     * @return array<string, mixed>
     */
    public function export(Booking $booking): array
    {
        $bookings = $this->bookings->sameGuest($booking);
        $messages = $booking->guest_email !== null ? $this->messages->byEmail($booking->guest_email) : new Collection;

        $this->audit->record(AuditAction::GuestDataExported, $booking, after: [
            'bookings' => $bookings->count(),
            'contact_messages' => $messages->count(),
        ]);

        return [
            'exported_at' => now()->toIso8601String(),
            'bookings' => $bookings->map(fn (Booking $item) => [
                'reference' => $item->reference,
                'apartment' => $item->apartment->getTranslation('name', 'fr'),
                'status' => $item->status->value,
                'arrival' => $item->arrival->toDateString(),
                'departure' => $item->departure->toDateString(),
                'adults' => $item->adults,
                'children' => $item->children,
                'guest' => [
                    'name' => $item->guest_name,
                    'email' => $item->guest_email,
                    'phone' => $item->guest_phone,
                    'country' => $item->guest_country,
                    'language' => $item->locale,
                    'message' => $item->guest_message,
                ],
                'extras' => $item->extras->map(fn (BookingExtra $extra) => ['name' => $extra->name, 'quantity' => $extra->quantity, 'amount_cents' => $extra->amount])->all(),
                'total_amount_cents' => $item->total_amount,
                'created_at' => $item->created_at?->toIso8601String(),
                'review' => $item->review?->submitted_at === null ? null : [
                    'rating' => $item->review->rating,
                    'comment' => $item->review->comment,
                    'display_name' => $item->review->display_name,
                    'submitted_at' => $item->review->submitted_at->toIso8601String(),
                ],
            ])->values()->all(),
            'contact_messages' => $messages->map(fn (ContactMessage $message) => [
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'message' => $message->message,
                'sent_at' => $message->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * Anonymise le voyageur de cette réservation (toutes ses réservations et ses messages).
     *
     * @throws GuestStayInProgressException séjour en attente ou confirmé, pas encore terminé
     */
    public function anonymize(Booking $booking): int
    {
        $bookings = $this->bookings->sameGuest($booking);
        $today = CarbonImmutable::today();
        $inProgress = $bookings->contains(fn (Booking $item) => in_array($item->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)
            && $item->departure->greaterThan($today));
        if ($inProgress) {
            throw new GuestStayInProgressException;
        }

        DB::transaction(function () use ($booking, $bookings) {
            if ($booking->guest_email !== null) {
                $this->messages->deleteByEmail($booking->guest_email);
            }
            $this->wipe($bookings->modelKeys());
            // Sans coordonnées : le journal ne garde que les références.
            $this->audit->record(AuditAction::GuestAnonymized, $booking, after: ['bookings' => $bookings->pluck('reference')->all()]);
        });

        return $bookings->count();
    }

    /** Tâche mensuelle : voyageurs partis depuis plus de 3 ans. */
    public function anonymizeExpired(CarbonImmutable $now): int
    {
        $ids = $this->bookings->idsToAnonymize($now->subYears(self::KEEP_YEARS));
        if ($ids !== []) {
            DB::transaction(fn () => $this->wipe($ids));
        }

        return count($ids);
    }

    /** @param  list<int>  $ids */
    private function wipe(array $ids): void
    {
        $this->bookings->anonymize($ids, self::PLACEHOLDER_NAME);
        $this->reviews->clearDisplayNames($ids);
        $this->coupons->anonymizeRedemptions($ids);
        $this->catalog->flush();
    }
}
