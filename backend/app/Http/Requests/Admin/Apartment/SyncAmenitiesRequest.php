<?php

namespace App\Http\Requests\Admin\Apartment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncAmenitiesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amenity_ids' => ['present', 'array'],
            'amenity_ids.*' => ['integer', 'distinct', Rule::exists('amenities', 'id')],
        ];
    }

    /**
     * @return list<int>
     */
    public function amenityIds(): array
    {
        return array_map('intval', $this->validated('amenity_ids'));
    }
}
