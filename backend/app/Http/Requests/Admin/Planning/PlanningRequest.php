<?php

namespace App\Http\Requests\Admin\Planning;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/** Période du planning : à partir d'un jour, de 7 à 62 jours. */
class PlanningRequest extends FormRequest
{
    public const DEFAULT_DAYS = 14;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'days' => ['sometimes', 'integer', 'between:7,62'],
        ];
    }

    public function from(): CarbonImmutable
    {
        return $this->filled('from')
            ? CarbonImmutable::parse($this->string('from')->toString())->startOfDay()
            : CarbonImmutable::today();
    }

    public function days(): int
    {
        return $this->filled('days') ? $this->integer('days') : self::DEFAULT_DAYS;
    }
}
