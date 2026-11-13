<?php

namespace App\Repositories\Contracts;

use App\Enums\IcalProvider;
use App\Models\IcalFeed;
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

    public function exists(int $apartmentId, IcalProvider $provider): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): IcalFeed;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(IcalFeed $feed, array $attributes): IcalFeed;
}
