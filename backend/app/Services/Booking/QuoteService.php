<?php

namespace App\Services\Booking;

use App\Exceptions\Booking\StayNotAllowedException;
use App\Models\Apartment;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\Quote;

/**
 * Devis d'un séjour : règles vérifiées, puis prix calculé par PriceCalculator.
 */
class QuoteService
{
    public function __construct(
        private readonly StayRules $rules,
        private readonly PriceCalculator $calculator,
    ) {}

    /**
     * Devis voyageur : toutes les règles doivent être respectées.
     *
     * @throws StayNotAllowedException
     */
    public function quote(Apartment $apartment, Stay $stay): Quote
    {
        $violations = $this->rules->violations($apartment, $stay);
        if ($violations !== []) {
            throw new StayNotAllowedException($violations);
        }

        return $this->calculator->calculate($apartment, $stay);
    }

    /**
     * Aperçu de l'hôte (écran Tarifs) : prix affiché dès que chaque nuit a un tarif, les
     * règles non respectées (séjour minimum…) étant signalées à côté.
     *
     * @return array{quote: Quote|null, warnings: list<array{code: string, message: string}>}
     */
    public function preview(Apartment $apartment, Stay $stay): array
    {
        $warnings = $this->rules->violations($apartment, $stay, forHost: true);
        $priceable = ! in_array('no_rate', array_column($warnings, 'code'), true);

        return [
            'quote' => $priceable ? $this->calculator->calculate($apartment, $stay) : null,
            'warnings' => $warnings,
        ];
    }
}
