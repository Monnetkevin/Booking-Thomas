<?php

namespace App\Services\Review;

use App\Enums\AuditAction;
use App\Enums\ReviewStatus;
use App\Exceptions\Review\ReviewNotSubmittedException;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\CatalogCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;

/**
 * Modération des avis (spec « Back-office › Avis ») : publier, refuser avec un motif
 * interne, répondre publiquement. Tous les avis sont traités de la même façon, quelle
 * que soit la note ; chaque décision est tracée et vide le cache du site (les navigateurs
 * peuvent garder l'ancienne liste une minute, comme pour les fiches logement).
 */
class ReviewModerationService
{
    public const PER_PAGE = 20;

    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
        private readonly AuditLogger $audit,
        private readonly CatalogCache $catalog,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Review>
     */
    public function list(?ReviewStatus $status, ?int $apartmentId): LengthAwarePaginator
    {
        return $this->reviews->paginateForAdmin($status, $apartmentId, self::PER_PAGE);
    }

    /**
     * @return array<string, int> nombre par statut, chaque statut présent
     */
    public function counts(?int $apartmentId): array
    {
        $counts = $this->reviews->countByStatus($apartmentId);

        return collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $status) => [$status->value => $counts[$status->value] ?? 0])->all();
    }

    public function publish(Review $review): Review
    {
        return $this->decide($review, AuditAction::ReviewPublished, [
            'status' => ReviewStatus::Published,
            'published_at' => $review->published_at ?? Date::now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(Review $review, ?string $reason): Review
    {
        return $this->decide($review, AuditAction::ReviewRejected, ['status' => ReviewStatus::Rejected, 'rejection_reason' => $reason]);
    }

    /** Réponse publique de l'hôte ; null l'efface. */
    public function reply(Review $review, ?string $reply): Review
    {
        return $this->decide($review, AuditAction::ReviewReplied, ['host_reply' => $reply]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function decide(Review $review, AuditAction $action, array $attributes): Review
    {
        if (! $review->status->isSubmitted()) {
            throw new ReviewNotSubmittedException;
        }

        $before = $this->snapshot($review);
        $this->reviews->update($review, $attributes);
        $this->audit->record($action, $review, $before, $this->snapshot($review));
        // Moyennes et avis publiés du site public.
        $this->catalog->flush();

        return $review;
    }

    /**
     * Ce que le journal garde : la décision, pas le texte du voyageur.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Review $review): array
    {
        return [
            'status' => $review->status->value,
            'rejection_reason' => $review->rejection_reason,
            'host_reply' => $review->host_reply,
        ];
    }
}
