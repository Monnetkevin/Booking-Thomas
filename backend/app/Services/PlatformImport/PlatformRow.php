<?php

namespace App\Services\PlatformImport;

use Carbon\CarbonImmutable;

/** Une réservation lue dans l'export d'une plateforme, montants en centimes. */
final readonly class PlatformRow
{
    /** Longueurs des colonnes en base (platform_payouts, platform_listings). */
    public const MAX_CODE = 64;

    public const MAX_LISTING = 190;

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

    /** Code et annonce assez courts pour être enregistrés. */
    public static function fits(string $code, string $listing): bool
    {
        return $code !== '' && mb_strlen($code) <= self::MAX_CODE && mb_strlen($listing) <= self::MAX_LISTING;
    }

    public function nights(): int
    {
        return (int) $this->arrival->diffInDays($this->departure);
    }
}
