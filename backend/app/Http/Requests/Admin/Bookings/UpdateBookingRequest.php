<?php

namespace App\Http\Requests\Admin\Bookings;

use Illuminate\Foundation\Http\FormRequest;

/** Coordonnées du voyageur et notes de ménage. Dates et voyageurs ne changent pas ici. */
class UpdateBookingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_name' => ['sometimes', 'string', 'max:120'],
            'guest_email' => ['sometimes', 'nullable', 'string', 'email', 'max:190'],
            'guest_phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'guest_country' => ['sometimes', 'nullable', 'string', 'size:2', 'alpha'],
            'housekeeping_notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function bookingData(): array
    {
        return array_map(function (mixed $value): ?string {
            $value = $value === null ? null : trim((string) $value);

            return $value === '' ? null : $value;
        }, $this->safe()->only(['guest_name', 'guest_email', 'guest_phone', 'guest_country', 'housekeeping_notes']));
    }
}
