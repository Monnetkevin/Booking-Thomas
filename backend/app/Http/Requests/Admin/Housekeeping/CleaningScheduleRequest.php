<?php

namespace App\Http\Requests\Admin\Housekeeping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Planning du ménage : à partir d'aujourd'hui, 14 jours (ou 30). */
class CleaningScheduleRequest extends FormRequest
{
    public const PERIODS = [14, 30];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', Rule::in(self::PERIODS)],
        ];
    }

    public function days(): int
    {
        return $this->filled('days') ? $this->integer('days') : self::PERIODS[0];
    }
}
