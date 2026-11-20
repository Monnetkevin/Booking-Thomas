<?php

namespace App\Services\PlatformImport;

use Carbon\CarbonImmutable;

/** Une réservation lue dans l'export d'une plateforme, montants en centimes. */
final readonly class PlatformRow
{
    public function __construct(
        public int $line,
        public string $confirmationCode,
        public string $listing,
        public ?string $guest,
        public CarbonImmutable $arrival,
        public CarbonImmutable $departure,
        public ?int $grossAmount,
        public ?int $fees,
        public int $netAmount,
    ) {}

    public function nights(): int
    {
        return (int) $this->arrival->diffInDays($this->departure);
    }
}
