<?php

namespace App\Http\Requests\Admin\PlatformRevenue;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/** Mois consulté (« 2026-10 ») : par défaut le mois précédent, jamais un mois à venir. */
class PlatformRevenueMonthRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['month' => ['sometimes', 'date_format:Y-m', 'after_or_equal:2020-01', 'before_or_equal:'.CarbonImmutable::today()->format('Y-m')]];
    }

    public function month(): CarbonImmutable
    {
        $month = $this->string('month')->toString();
        $previous = CarbonImmutable::today()->startOfMonth()->subMonth();

        return $month === '' ? $previous : (CarbonImmutable::createFromFormat('!Y-m', $month) ?: $previous);
    }
}
