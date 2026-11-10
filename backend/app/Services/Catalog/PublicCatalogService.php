<?php

namespace App\Services\Catalog;

use App\Enums\CancellationPolicy;
use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Logements publiés, tels que les voit le site public. Lectures mises en cache
 * (CatalogCache) : les modèles gardent toutes leurs traductions, la langue est
 * appliquée au moment de la réponse.
 */
class PublicCatalogService
{
    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly SettingRepositoryInterface $settings,
        private readonly CatalogCache $cache,
    ) {}

    /**
     * @return Collection<int, Apartment>
     */
    public function published(): Collection
    {
        return $this->cache->remember('apartments', fn () => $this->apartments->publishedWithCatalogData());
    }

    /**
     * @throws ModelNotFoundException logement inconnu, supprimé ou en brouillon
     */
    public function findPublished(string $slug): Apartment
    {
        $apartment = $this->cache->remember("apartment:$slug", fn () => $this->apartments->findPublishedBySlug($slug));

        if (! $apartment) {
            throw (new ModelNotFoundException)->setModel(Apartment::class, [$slug]);
        }

        return $apartment;
    }

    /**
     * Paliers du barème d'annulation, du plus tôt au plus tard.
     *
     * @return list<array{days_before: int, refund_percent: int}>
     */
    public function cancellationTiers(CancellationPolicy $policy): array
    {
        /** @var array<string, list<array{days_before: int, refund_percent: int}>> $policies */
        $policies = $this->settings->get('cancellation_policies', []);

        return $policies[$policy->value] ?? [];
    }
}
