<?php

namespace App\Services\Search;

use App\Models\Apartment;
use App\Models\Booking;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Recherche rapide (Ctrl K) : réservations par référence, nom ou e-mail du voyageur, et
 * logements par nom. Les pages du back-office sont cherchées par la SPA elle-même.
 */
class AdminSearchService
{
    public const LIMIT = 6;

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly ApartmentRepositoryInterface $apartments,
    ) {}

    /**
     * @return array{bookings: Collection<int, Booking>, apartments: Collection<int, Apartment>}
     */
    public function search(string $term): array
    {
        $needle = Str::lower(Str::ascii($term));

        return [
            'bookings' => $this->bookings->search($term, self::LIMIT),
            // Quelques logements : filtrés ici, accents et casse ignorés.
            'apartments' => $this->apartments->allOrdered()
                ->filter(fn (Apartment $apartment) => str_contains(Str::lower(Str::ascii($apartment->getTranslation('name', 'fr'))), $needle))
                ->take(self::LIMIT)
                ->values(),
        ];
    }
}
