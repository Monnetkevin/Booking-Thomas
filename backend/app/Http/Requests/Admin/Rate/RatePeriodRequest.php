<?php

namespace App\Http\Requests\Admin\Rate;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Période tarifaire : dates incluses, montants en centimes, jours d'arrivée ISO (1 = lundi).
 */
class RatePeriodRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:80'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'nightly_price' => ['required', 'integer', 'between:100,1000000'],
            'min_stay' => ['nullable', 'integer', 'between:1,365'],
            'weekend_supplement' => ['nullable', 'integer', 'between:0,1000000'],
            'arrival_days' => ['nullable', 'array', 'min:1'],
            'arrival_days.*' => ['integer', 'between:1,7', 'distinct'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function periodData(): array
    {
        $data = $this->validated();
        $data['label'] = filled($data['label'] ?? null) ? trim($data['label']) : null;

        if (isset($data['arrival_days'])) {
            $days = array_map('intval', $data['arrival_days']);
            sort($days);
            // Tous les jours cochés = aucune restriction.
            $data['arrival_days'] = count($days) === 7 ? null : $days;
        }

        return $data;
    }
}
