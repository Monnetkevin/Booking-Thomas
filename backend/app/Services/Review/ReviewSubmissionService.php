<?php

namespace App\Services\Review;

use App\Enums\ReviewStatus;
use App\Events\ReviewSubmitted;
use App\Exceptions\Review\ReviewInvitationInvalidException;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Date;

/**
 * Avis déposé par le voyageur depuis son lien personnel (spec « Avis voyageurs ») : le lien
 * ne sert qu'une fois et 30 jours au plus ; l'avis attend la modération, jamais publié seul.
 */
class ReviewSubmissionService
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
        private readonly ConnectionInterface $db,
    ) {}

    /** Invitation encore utilisable, avec sa réservation et son logement. */
    public function invitation(string $token): Review
    {
        return $this->usable($this->reviews->findByTokenHash(ReviewInvitationService::hash($token)));
    }

    /**
     * @param  array{rating: int, comment: string, display_name: string}  $data
     */
    public function submit(string $token, array $data): Review
    {
        $review = $this->db->transaction(function () use ($token, $data): Review {
            // Verrou : deux envois simultanés du même lien ne déposent qu'un avis.
            $review = $this->usable($this->reviews->findByTokenHash(ReviewInvitationService::hash($token), lock: true));

            return $this->reviews->update($review, [
                ...$data,
                'status' => ReviewStatus::Pending,
                'submitted_at' => Date::now(),
            ]);
        });

        ReviewSubmitted::dispatch($review);

        return $review;
    }

    private function usable(?Review $review): Review
    {
        if ($review === null || $review->status !== ReviewStatus::Invited || $review->expires_at->isPast()) {
            throw new ReviewInvitationInvalidException;
        }

        return $review;
    }
}
