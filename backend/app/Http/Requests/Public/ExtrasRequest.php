<?php

namespace App\Http\Requests\Public;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/** Options d'un logement pour une date d'arrivée. */
class ExtrasRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['arrival' => QuoteRequest::stayRules()['arrival']];
    }

    public function arrival(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('arrival')->toString())->startOfDay();
    }
}
