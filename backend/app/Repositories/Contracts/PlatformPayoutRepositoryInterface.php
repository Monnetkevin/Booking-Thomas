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
     * @param  array<string, int>  $map  nom de l'annonce → id du logement
     */
    public function saveListingMap(PayoutPlatform $platform, array $map): void;

    /**
     * Codes de confirmation déjà importés parmi ceux-ci.
     *
     * @param  list<string>  $codes
     * @return list<string>
     */
    public function existingCodes(PayoutPlatform $platform, array $codes): array;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createImport(array $attributes): PlatformImport;

    public function updateImport(PlatformImport $import, array $attributes): PlatformImport;

    /**
     * Crée ou met à jour le revenu (clé : plateforme + code de confirmation) ; vrai si créé.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsert(PayoutPlatform $platform, string $code, array $attributes): bool;

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
