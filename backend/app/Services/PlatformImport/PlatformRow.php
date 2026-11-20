<?php

namespace App\Services\PlatformImport;

use Carbon\CarbonImmutable;

/** Un versement lu dans l'export d'une plateforme (une ligne), montants en centimes. */
final readonly class PlatformRow
{
    /** Longueurs des colonnes en base (platform_payouts, platform_listings). */
    public const MAX_CODE = 64;

    public const MAX_LISTING = 190;

    /** Bornes de la base : nuits (petit entier) et montants (entier 32 bits), avec de la marge. */
    public const MAX_NIGHTS = 3650;

    public const MAX_AMOUNT = 1_000_000_000;

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
        /** Identifiant stable du versement (même ligne ré-importée : même clé). */
        public string $entryKey,
    ) {}

    /** Code et annonce présents, assez courts pour être enregistrés (une annonce vide ne se rattache à aucun logement). */
    public static function fits(string $code, string $listing): bool
    {
        return $code !== '' && mb_strlen($code) <= self::MAX_CODE && $listing !== '' && mb_strlen($listing) <= self::MAX_LISTING;
    }

    /** Nuits et montants dans les bornes de la base (sinon la ligne est ignorée). */
    public function inRange(): bool
    {
        $amounts = array_filter([$this->grossAmount, $this->fees, $this->netAmount], fn (?int $amount) => $amount !== null);

        return $this->nights() >= 1 && $this->nights() <= self::MAX_NIGHTS
            && collect($amounts)->every(fn (int $amount) => abs($amount) <= self::MAX_AMOUNT);
    }

    /** Clé d'un versement à partir des valeurs qui le distinguent dans le fichier. */
    public static function key(string ...$parts): string
    {
        return hash('sha256', implode('|', $parts));
    }

    public function nights(): int
    {
        return (int) $this->arrival->diffInDays($this->departure);
    }
}
