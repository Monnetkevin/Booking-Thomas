<?php

namespace App\Repositories\Contracts;

use App\Enums\PayoutPlatform;
use App\Models\PlatformImport;
use App\Models\PlatformPayout;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

interface PlatformPayoutRepositoryInterface
{
    /**
     * Annonces déjà rattachées : nom de l'annonce → id du logement.
     *
     * @return array<string, int>
     */
    public function listingMap(PayoutPlatform $platform): array;

    /**
     * Enregistre les rattachements ; une annonce mise à null est détachée.
     *
     * @param  array<string, int|null>  $map  nom de l'annonce → id du logement
     */
    public function saveListingMap(PayoutPlatform $platform, array $map): void;

    /**
     * Versements déjà importés parmi ces clés.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public function existingKeys(PayoutPlatform $platform, array $keys): array;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createImport(array $attributes): PlatformImport;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateImport(PlatformImport $import, array $attributes): PlatformImport;

    /**
     * Crée ou met à jour le versement (clé : plateforme + clé du versement) ; vrai si créé.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsert(PayoutPlatform $platform, string $entryKey, array $attributes): bool;

    /**
     * @return Collection<int, PlatformImport>
     */
    public function recentImports(int $limit): Collection;

    /**
     * Revenus des plateformes arrivant dans [from, to[.
     *
     * @return Collection<int, PlatformPayout>
     */
    public function arrivingBetween(CarbonInterface $from, CarbonInterface $to): Collection;
}
