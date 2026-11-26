<?php

namespace App\Services\Review;

use App\Enums\ReviewStatus;
use App\Exceptions\Review\ReviewAlreadySubmittedException;
use App\Mail\ReviewInvitationGuest;
use App\Models\Booking;
use App\Models\Review;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * Invitations à laisser un avis (spec « Avis voyageurs ») : chaque jour, les séjours dont
 * le départ est passé deviennent « terminés », puis chaque voyageur parti la veille reçoit
 * un lien personnel, à usage unique, valable 30 jours. Seule l'empreinte du jeton est gardée.
 */
class ReviewInvitationService
{
    public const VALID_DAYS = 30;

    /** Un passage manqué (serveur arrêté) est rattrapé sur cette durée, sans relancer les plus anciens. */
    private const CATCH_UP_DAYS = 7;

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly ReviewRepositoryInterface $reviews,
        private readonly Mailer $mailer,
    ) {}

    /** Réservations confirmées dont le départ est passé → terminées. */
    public function completeDepartedStays(): int
    {
        return $this->bookings->completeDepartedBefore(CarbonImmutable::instance(Date::today()));
    }

    /** @return int nombre d'invitations envoyées */
    public function sendDueInvitations(): int
    {
        $yesterday = CarbonImmutable::instance(Date::today())->subDay();
        $due = $this->bookings->awaitingReviewInvitation($yesterday->subDays(self::CATCH_UP_DAYS - 1), $yesterday);

        $due->each(function (Booking $booking): void {
            [$token, $attributes] = $this->freshToken();
            $this->reviews->create([...$attributes, 'booking_id' => $booking->id, 'apartment_id' => $booking->apartment_id, 'status' => ReviewStatus::Invited]);
            $this->send($booking, $token, $attributes['expires_at']);
        });

        return $due->count();
    }

    /** Nouvelle invitation (nouveau lien, 30 jours de plus), tant que le voyageur n'a pas répondu. */
    public function resend(Review $review): Review
    {
        if ($review->status->isSubmitted()) {
            throw new ReviewAlreadySubmittedException;
        }

        [$token, $attributes] = $this->freshToken();
        $this->reviews->update($review, $attributes);
        $this->send($review->booking, $token, $attributes['expires_at']);

        return $review;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return array{0: string, 1: array{token_hash: string, invited_at: CarbonImmutable, expires_at: CarbonImmutable}}
     */
    private function freshToken(): array
    {
        $token = Str::random(48);
        $now = CarbonImmutable::instance(Date::now());

        return [$token, ['token_hash' => self::hash($token), 'invited_at' => $now, 'expires_at' => $now->addDays(self::VALID_DAYS)]];
    }

    private function send(Booking $booking, string $token, CarbonImmutable $expiresAt): void
    {
        if ($booking->guest_email !== null) {
            $this->mailer->to(new Address($booking->guest_email, $booking->guest_name))->queue(new ReviewInvitationGuest($booking, $token, $expiresAt));
        }
    }
}
