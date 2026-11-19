<?php

namespace App\Repositories\Contracts;

use App\Enums\IcalProvider;
use App\Models\IcalFeed;
use App\Models\IcalSyncLog;
use Illuminate\Database\Eloquent\Collection;

interface IcalFeedRepositoryInterface
{
    /**
     * Flux à relire régulièrement : actifs et dotés d'une URL d'import.
     *
     * @return Collection<int, IcalFeed>
     */
    public function importable(): Collection;

    /**
     * Tous les flux, avec leur logement, pour le back-office.
     *
     * @return Collection<int, IcalFeed>
     */
    public function all(): Collection;

    /** Flux dont l'URL d'export porte ce token. */
    public function findByExportToken(string $token): ?IcalFeed;

    public function exists(int $apartmentId, IcalProvider $provider): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): IcalFeed;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(IcalFeed $feed, array $attributes): IcalFeed;

    /**
     * Enregistre l'issue d'une synchronisation sur le flux et dans son journal.
     *
     * @param  array<string, mixed>  $feedAttributes
     * @param  array<string, mixed>  $log
     */
    public function recordSync(IcalFeed $feed, array $feedAttributes, array $log): IcalSyncLog;

    /**
     * Dernières synchronisations du flux, de la plus récente à la plus ancienne.
     *
     * @return Collection<int, IcalSyncLog>
     */
    public function recentLogs(IcalFeed $feed, int $limit): Collection;
}
