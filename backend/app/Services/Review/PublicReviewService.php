<?php

namespace App\Services\Review;

use App\Models\Apartment;
use App\Models\Review;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Services\Catalog\CatalogCache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Avis publiés, tels que les voit le site (spec « Avis voyageurs ») : prénom, mois du
 * séjour, « séjour vérifié », réponse de l'hôte ; avec la moyenne et la répartition des
 * notes. Tableaux mis en cache avec le catalogue, vidés à chaque décision de l'hôte.
 */
class PublicReviewService
{
    public const PER_PAGE = 6;

    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly CatalogCache $cache,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, total: int}, summary: array{average: float|null, count: int, distribution: array<int, int>}}
     *
     * @throws ModelNotFoundException logement inconnu ou pas en ligne
     */
    public function page(?string $slug, int $page, string $locale): array
    {
        $apartmentId = $slug === null ? null : $this->apartmentId($slug);

        return $this->cache->remember('reviews:'.($slug ?? '*').":$page:$locale", function () use ($apartmentId, $page, $locale): array {
            $reviews = $this->reviews->paginatePublished($apartmentId, self::PER_PAGE, $page);

            return [
                'data' => array_values(array_map(fn (Review $review) => $this->present($review, $locale), $reviews->items())),
                'meta' => ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()],
                'summary' => $this->summary($this->reviews->publishedRatings($apartmentId)),
            ];
        });
    }

    /**
     * @param  array<int, int>  $ratings  nombre d'avis par note
     * @return array{average: float|null, count: int, distribution: array<int, int>}
     */
    private function summary(array $ratings): array
    {
        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $rating) {
            $distribution[$rating] = $ratings[$rating] ?? 0;
        }
        $count = array_sum($distribution);
        $total = array_sum(array_map(fn (int $rating, int $number) => $rating * $number, array_keys($distribution), $distribution));

        return ['average' => $count === 0 ? null : round($total / $count, 1), 'count' => $count, 'distribution' => $distribution];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Review $review, string $locale): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'display_name' => $review->display_name,
            // Mois du séjour seulement : jamais les dates exactes.
            'stay_month' => $review->booking->arrival->format('Y-m'),
            'apartment' => ['name' => $review->apartment->getTranslation('name', $locale), 'slug' => $review->apartment->slug],
            'host_reply' => $review->host_reply,
            'published_on' => $review->published_at?->toDateString(),
        ];
    }

    private function apartmentId(string $slug): int
    {
        $apartment = $this->apartments->findPublishedBySlug($slug);
        if ($apartment === null) {
            throw (new ModelNotFoundException)->setModel(Apartment::class, [$slug]);
        }

        return $apartment->id;
    }
}
