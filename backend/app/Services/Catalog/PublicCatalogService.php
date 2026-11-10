<?php

namespace App\Services\Catalog;

use App\Enums\CancellationPolicy;
use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Logements publiés, tels que les voit le site public.
 *
 * - summaries() et detail() : tableaux prêts à renvoyer, mis en cache par langue
 *   (CatalogCache ; aucun objet PHP n'est mis en cache).
 * - published() et findPublished() : modèles à jour, sans cache, pour les calculs
 *   (devis, disponibilités, recherche).
 */
class PublicCatalogService
{
    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly SettingRepositoryInterface $settings,
        private readonly CatalogPresenter $presenter,
        private readonly CatalogCache $cache,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function summaries(string $locale): array
    {
        return $this->cache->remember("summaries:$locale", fn () => $this->published()
            ->map(fn (Apartment $apartment) => $this->presenter->summary($apartment, $locale))
            ->values()
            ->all());
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ModelNotFoundException logement inconnu, supprimé ou en brouillon
     */
    public function detail(string $slug, string $locale): array
    {
        $detail = $this->cache->remember("detail:$slug:$locale", function () use ($slug, $locale) {
            $apartment = $this->apartments->findPublishedBySlug($slug);

            return $apartment
                ? $this->presenter->detail($apartment, $locale, $this->cancellationTiers($apartment->cancellation_policy))
                : false;
        });

        if ($detail === false) {
            throw (new ModelNotFoundException)->setModel(Apartment::class, [$slug]);
        }

        return $detail;
    }

    /**
     * @return Collection<int, Apartment>
     */
    public function published(): Collection
    {
        return $this->apartments->publishedWithCatalogData();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findPublished(string $slug): Apartment
    {
        return $this->apartments->findPublishedBySlug($slug)
            ?? throw (new ModelNotFoundException)->setModel(Apartment::class, [$slug]);
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
