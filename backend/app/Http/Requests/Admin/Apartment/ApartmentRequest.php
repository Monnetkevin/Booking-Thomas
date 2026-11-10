<?php

namespace App\Http\Requests\Admin\Apartment;

use App\Enums\CancellationPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Champs d'une fiche logement, identiques à la création et à la modification (PUT complet).
 * Montants en centimes, heures au format HH:MM. L'adresse de la page (slug) est tirée du nom
 * et les coordonnées de l'adresse postale : ni l'un ni les autres ne sont saisis.
 */
abstract class ApartmentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.fr' => ['required', 'string', 'max:120'],
            'name.en' => ['nullable', 'string', 'max:120'],
            'tagline' => ['nullable', 'array'],
            'tagline.fr' => ['nullable', 'string', 'max:80'],
            'tagline.en' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'array'],
            'description.fr' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'city' => ['required', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'between:1,20'],
            'bedrooms' => ['required', 'integer', 'between:0,20'],
            'beds' => ['required', 'integer', 'between:1,30'],
            'bathrooms' => ['required', 'integer', 'between:0,10'],
            'surface_m2' => ['nullable', 'integer', 'between:5,1000'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'min_stay' => ['required', 'integer', 'between:1,365'],
            'max_stay' => ['required', 'integer', 'between:1,365', 'gte:min_stay'],
            'turnover_days' => ['required', 'integer', 'between:0,7'],
            'cleaning_fee' => ['required', 'integer', 'between:0,100000'],
            'cancellation_policy' => ['required', Rule::enum(CancellationPolicy::class)],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => ['integer', 'distinct', Rule::exists('amenities', 'id')],
        ];
    }

    /**
     * Données validées, sans les traductions vides (le français sert de repli).
     *
     * @return array<string, mixed>
     */
    public function apartmentData(): array
    {
        $data = $this->validated();

        foreach (['name', 'tagline', 'description'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field], fn ($value) => filled($value));
            }
        }

        return $data;
    }
}
