<?php

namespace App\Services\Booking;

use Carbon\CarbonImmutable;

/**
 * Séjour demandé : nuits [arrival, departure[, voyageurs. Les enfants (mineurs) comptent
 * dans la capacité mais sont exonérés de taxe de séjour.
 */
final readonly class Stay
{
    public function __construct(
        public CarbonImmutable $arrival,
        public CarbonImmutable $departure,
        public int $adults,
        public int $children = 0,
    ) {}

    public static function fromStrings(string $arrival, string $departure, int $adults, int $children = 0): self
    {
        return new self(
            CarbonImmutable::parse($arrival)->startOfDay(),
            CarbonImmutable::parse($departure)->startOfDay(),
            $adults,
            $children,
        );
    }

    public function nights(): int
    {
        return (int) $this->arrival->diffInDays($this->departure);
    }

    public function guests(): int
    {
        return $this->adults + $this->children;
    }

    /**
     * Dates des nuits du séjour (la nuit du départ n'en fait pas partie).
     *
     * @return list<CarbonImmutable>
     */
    public function nightDates(): array
    {
        $dates = [];
        for ($night = $this->arrival; $night->lt($this->departure); $night = $night->addDay()) {
            $dates[] = $night;
        }

        return $dates;
    }
}
