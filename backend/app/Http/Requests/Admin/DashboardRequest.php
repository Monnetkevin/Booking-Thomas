<?php

namespace App\Http\Requests\Admin;

use App\Enums\DashboardPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Période des chiffres du tableau de bord : semaine, mois (par défaut) ou année. */
class DashboardRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['period' => ['sometimes', Rule::enum(DashboardPeriod::class)]];
    }

    public function period(): DashboardPeriod
    {
        return DashboardPeriod::tryFrom($this->string('period')->toString()) ?? DashboardPeriod::Month;
    }
}
