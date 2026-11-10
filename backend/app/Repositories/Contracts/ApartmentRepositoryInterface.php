<?php

namespace App\Repositories\Contracts;

use App\Enums\ApartmentStatus;
use App\Models\Apartment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

interface ApartmentRepositoryInterface
{
    /**
     * Logements dans l'ordre d'affichage, avec photos et périodes à venir.
     *
     * @return Collection<int, Apartment>
     */
    public function list(?ApartmentStatus $status = null): Collection;

    /**
     * Logements publiés, dans l'ordre, avec photos, équipements et périodes à venir.
     *
     * @return Collection<int, Apartment>
     */
    public function publishedWithCatalogData(): Collection;

    /** Logement publié et tout ce qu'affiche sa fiche publique, ou null. */
    public function findPublishedBySlug(string $slug): ?Apartment;

    public function idForSlug(string $slug): ?int;

    /** Charge tout ce qu'affiche la fiche d'administration. */
    public function loadDetails(Apartment $apartment): Apartment;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Apartment;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Apartment $apartment, array $attributes): Apartment;

    public function delete(Apartment $apartment): void;

    public function slugExists(string $slug, ?int $exceptId = null): bool;

    public function nextPosition(): int;

    /** Un séjour (en cours ou à venir) occupe-t-il encore des nuits après $today ? */
    public function hasUpcomingStays(Apartment $apartment, CarbonInterface $today): bool;

    public function hasUpcomingRatePeriod(Apartment $apartment, CarbonInterface $today): bool;

    public function photoCount(Apartment $apartment): int;

    /**
     * Logements dont la position n'a jamais été calculée.
     *
     * @return Collection<int, Apartment>
     */
    public function awaitingGeocoding(): Collection;

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;

    /**
     * @param  list<int>  $amenityIds
     */
    public function syncAmenities(Apartment $apartment, array $amenityIds): void;
}
