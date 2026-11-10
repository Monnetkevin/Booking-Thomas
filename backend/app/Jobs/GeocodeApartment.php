<?php

namespace App\Jobs;

use App\Models\Apartment;
use App\Services\Apartment\ApartmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Calcule la position du logement sur la carte d'après son adresse. */
class GeocodeApartment implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> secondes entre les tentatives si le service est indisponible */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public readonly Apartment $apartment)
    {
        $this->afterCommit();
    }

    public function handle(ApartmentService $apartments): void
    {
        $apartments->refreshCoordinates($this->apartment);
    }
}
