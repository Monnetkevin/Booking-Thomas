<?php

namespace App\Services\Pricing;

/**
 * Détail du prix d'un séjour, en centimes. Copié tel quel dans la réservation à sa
 * création : modifier les tarifs ensuite ne la change pas.
 */
final readonly class Quote
{
    /**
     * @param  list<array{date: string, price: int, weekend_supplement: int}>  $nights
     * @param  list<array{type: string, label: string, amount: int, quantity?: int, unit_amount?: int}>  $lines
     */
    public function __construct(
        public array $nights,
        public int $accommodation,
        public int $cleaningFee,
        public int $touristTax,
        public array $lines,
        public int $total,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nights' => $this->nights,
            'accommodation' => $this->accommodation,
            'cleaning_fee' => $this->cleaningFee,
            'tourist_tax' => $this->touristTax,
            'lines' => $this->lines,
            'total' => $this->total,
            'currency' => 'EUR',
        ];
    }
}
