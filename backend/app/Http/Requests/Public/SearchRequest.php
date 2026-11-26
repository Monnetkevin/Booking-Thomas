<?php

namespace App\Http\Requests\Public;

use App\Services\Booking\Stay;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'arrival' => ['required', 'date_format:Y-m-d'],
            'departure' => ['required', 'date_format:Y-m-d', 'after:arrival'],
            'guests' => ['sometimes', 'integer', 'between:1,20'],
        ];
    }

    /** Les voyageurs recherchés comptent comme adultes (le devis précisera les enfants). */
    public function stay(): Stay
    {
        return Stay::fromStrings(
            $this->string('arrival')->toString(),
            $this->string('departure')->toString(),
            $this->integer('guests', 1),
        );
    }
}
