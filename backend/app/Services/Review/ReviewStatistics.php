<?php

namespace App\Services\Review;

use App\Enums\ReviewStatus;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Chiffres des avis pour le back-office (écran Avis et tableau de bord) : note moyenne et
 * nombre d'avis publiés, avis à modérer, part des avis à 5 étoiles, et taux de réponse de
 * l'hôte aux avis reçus ces 30 derniers jours.
 */
class ReviewStatistics
{
    public const REPLY_WINDOW_DAYS = 30;

    public function __construct(private readonly ReviewRepositoryInterface $reviews) {}

    /**
     * @return array{average: float|null, count: int, pending: int, five_star_share: int|null, reply_rate: int|null}
     */
    public function summary(): array
    {
        $ratings = $this->reviews->publishedRatings(null);
        $count = array_sum($ratings);
        $total = array_sum(array_map(fn (int $rating, int $number) => $rating * $number, array_keys($ratings), $ratings));
        $replies = $this->reviews->repliesSince(CarbonImmutable::instance(Date::now())->subDays(self::REPLY_WINDOW_DAYS));

        return [
            'average' => $count === 0 ? null : round($total / $count, 1),
            'count' => $count,
            'pending' => $this->pending(),
            'five_star_share' => $count === 0 ? null : (int) round(($ratings[5] ?? 0) * 100 / $count),
            'reply_rate' => $replies['received'] === 0 ? null : (int) round($replies['replied'] * 100 / $replies['received']),
        ];
    }

    /** Avis déposés, en attente de modération. */
    public function pending(): int
    {
        return $this->reviews->countByStatus(null)[ReviewStatus::Pending->value] ?? 0;
    }
}
