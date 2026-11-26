<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use App\Models\Extra;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

interface ExtraRepositoryInterface
{
    /**
     * Options actives proposées dans ce logement, dans l'ordre du catalogue.
     *
     * @return Collection<int, Extra>
     */
    public function activeForApartment(int $apartmentId): Collection;

    /**
     * Option active et proposée dans un de ces logements (dans au moins un logement si la
     * liste est vide).
     *
     * @param  list<int>  $apartmentIds
     */
    public function isOfferedIn(int $extraId, array $apartmentIds): bool;

    /**
     * Options figées dans la réservation.
     *
     * @param  list<array{extra_id: int, name: string, pricing: string, quantity: int, unit_amount: int, amount: int}>  $items
     */
    public function recordForBooking(Booking $booking, array $items): void;

    /**
     * Tout le catalogue, avec les logements où chaque option est proposée.
     *
     * @return Collection<int, Extra>
     */
    public function all(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function create(array $attributes, array $apartmentIds): Extra;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function update(Extra $extra, array $attributes, array $apartmentIds): Extra;

    /**
     * Réservations confirmées ou terminées qui arrivent dans [from, to] avec des options,
     * dans l'ordre des arrivées, avec leur logement et leurs options.
     *
     * @return Collection<int, Booking>
     */
    public function bookingsToPrepare(CarbonInterface $from, CarbonInterface $to): Collection;

    /**
     * Chiffres d'un mois d'arrivées (réservations confirmées ou terminées) : réservations,
     * réservations avec options, valeur des options commandées en centimes, après
     * la remise d'un code ou d'une offre sur les options (prix convenus : 0).
     *
     * @return array{bookings: int, with_extras: int, revenue: int}
     */
    public function monthFigures(CarbonInterface $from, CarbonInterface $to): array;
}
