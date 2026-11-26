<?php

namespace App\Services\Extra;

/** Option demandée pour un séjour : son identifiant et, pour une option à l'unité, la quantité. */
final readonly class ExtraChoice
{
    public function __construct(public int $extraId, public int $quantity = 1) {}

    /**
     * @param  list<array{id: int, quantity?: int|null}>  $input
     * @return list<self>
     */
    public static function list(array $input): array
    {
        return array_map(fn (array $item) => new self((int) $item['id'], (int) ($item['quantity'] ?? 1)), $input);
    }
}
