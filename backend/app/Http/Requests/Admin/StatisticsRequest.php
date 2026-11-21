<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatisticsPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Période des statistiques : 6 mois, 12 mois (par défaut) ou tout. */
class StatisticsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['period' => ['sometimes', Rule::enum(StatisticsPeriod::class)]];
    }

    public function period(): StatisticsPeriod
    {
        return StatisticsPeriod::tryFrom($this->string('period')->toString()) ?? StatisticsPeriod::Year;
    }
}
