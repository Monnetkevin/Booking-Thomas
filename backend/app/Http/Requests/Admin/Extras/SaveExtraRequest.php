<?php

namespace App\Http\Requests\Admin\Extras;

use App\Enums\ExtraPricing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Option du catalogue : nom (français requis, anglais facultatif), description, prix en
 * centimes (0 = gratuite), mode de prix, quantité maximale, délai de commande en heures,
 * activation et logements concernés.
 */
class SaveExtraRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['name_fr', 'name_en', 'description_fr', 'description_en'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_fr' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'description_fr' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'pricing' => ['required', Rule::enum(ExtraPricing::class)],
            'max_quantity' => ['required', 'integer', 'between:1,99'],
            'lead_time_hours' => ['required', 'integer', 'between:0,720'],
            'is_active' => ['required', 'boolean'],
            'apartment_ids' => ['present', 'array'],
            'apartment_ids.*' => ['integer', 'distinct', Rule::exists('apartments', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function extraAttributes(): array
    {
        $pricing = ExtraPricing::from($this->string('pricing')->toString());

        return [
            'name' => array_filter(['fr' => $this->validated('name_fr'), 'en' => $this->validated('name_en')]),
            'description' => array_filter(['fr' => $this->validated('description_fr'), 'en' => $this->validated('description_en')]),
            'price' => $this->integer('price'),
            'pricing' => $pricing,
            // Seule une option à l'unité se commande en plusieurs exemplaires.
            'max_quantity' => $pricing->hasQuantity() ? $this->integer('max_quantity') : 1,
            'lead_time_hours' => $this->integer('lead_time_hours'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return list<int>
     */
    public function apartmentIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('apartment_ids')));
    }
}
