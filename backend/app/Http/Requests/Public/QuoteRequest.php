<?php

namespace App\Http\Requests\Public;

use App\Http\Requests\Concerns\ReadsExtraChoices;
use App\Services\Booking\Stay;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    use ReadsExtraChoices;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment' => ['required', 'string', 'max:100'],
            ...self::stayRules(),
            ...self::extraRules(),
        ];
    }

    /** Dates acceptées autour d'aujourd'hui, en années (calcul nuit par nuit borné). */
    private const DATE_RANGE_YEARS = 3;

    /**
     * Règles communes au devis public, à la réservation et à l'aperçu de l'hôte. Les dates sont
     * bornées : un départ lointain (an 9999) ferait parcourir des millions de nuits au serveur.
     *
     * @return array<string, list<string>>
     */
    public static function stayRules(): array
    {
        $today = CarbonImmutable::today();
        $earliest = $today->subYears(self::DATE_RANGE_YEARS)->toDateString();
        $latest = $today->addYears(self::DATE_RANGE_YEARS)->toDateString();

        return [
            'arrival' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$earliest, 'before_or_equal:'.$latest],
            'departure' => ['required', 'date_format:Y-m-d', 'after:arrival', 'before_or_equal:'.$latest],
            'adults' => ['required', 'integer', 'between:1,20'],
            'children' => ['sometimes', 'integer', 'between:0,20'],
        ];
    }

    public function stay(): Stay
    {
        return Stay::fromStrings(
            $this->string('arrival')->toString(),
            $this->string('departure')->toString(),
            $this->integer('adults'),
            $this->integer('children'),
        );
    }
}
