<?php

namespace App\Http\Requests\Public;

use App\Services\Booking\Stay;
use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment' => ['required', 'string', 'max:100'],
            ...self::stayRules(),
        ];
    }

    /**
     * Règles communes au devis public et à l'aperçu de l'hôte.
     *
     * @return array<string, list<string>>
     */
    public static function stayRules(): array
    {
        return [
            'arrival' => ['required', 'date_format:Y-m-d'],
            'departure' => ['required', 'date_format:Y-m-d', 'after:arrival'],
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
