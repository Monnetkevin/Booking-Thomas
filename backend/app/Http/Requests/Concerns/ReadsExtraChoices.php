<?php

namespace App\Http\Requests\Concerns;

use App\Services\Extra\ExtraChoice;

/**
 * Options demandées pour un séjour : « extras » = [{id, quantity}], chaque option une fois.
 * Leur disponibilité et leur prix sont vérifiés par le serveur (ExtraSelector, PriceCalculator).
 */
trait ReadsExtraChoices
{
    /**
     * @return array<string, list<string>>
     */
    public static function extraRules(): array
    {
        return [
            'extras' => ['sometimes', 'array', 'max:20'],
            'extras.*.id' => ['required', 'integer', 'distinct'],
            'extras.*.quantity' => ['sometimes', 'integer', 'between:1,99'],
        ];
    }

    /**
     * @return list<ExtraChoice>
     */
    public function extras(): array
    {
        /** @var list<array{id: int, quantity?: int|null}> $extras */
        $extras = array_values((array) ($this->validated('extras') ?? []));

        return ExtraChoice::list($extras);
    }
}
