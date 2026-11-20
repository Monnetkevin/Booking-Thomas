<?php

namespace App\Http\Requests\Admin\Bookings;

use App\Enums\BookingSource;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;

/** Coordonnées du voyageur et notes de ménage. Dates et voyageurs ne changent pas ici. */
class UpdateBookingRequest extends FormRequest
{
    /** Mêmes normalisations qu'à la création : champs vides → null, e-mail en minuscules, pays en majuscules. */
    protected function prepareForValidation(): void
    {
        foreach (['guest_name', 'guest_email', 'guest_phone', 'guest_country', 'housekeeping_notes'] as $field) {
            if ($this->has($field) && $this->input($field) !== null) {
                $value = trim((string) $this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
        if (is_string($this->input('guest_email'))) {
            $this->merge(['guest_email' => mb_strtolower($this->input('guest_email'))]);
        }
        if (is_string($this->input('guest_country'))) {
            $this->merge(['guest_country' => mb_strtoupper($this->input('guest_country'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_name' => ['sometimes', 'string', 'max:120'],
            // Une réservation du site garde toujours son e-mail (confirmation, remboursement).
            'guest_email' => ['sometimes', $this->isDirect() ? 'required' : 'nullable', 'string', 'email', 'max:190'],
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
        /** @var array<string, string|null> $data */
        $data = $this->safe()->only(['guest_name', 'guest_email', 'guest_phone', 'guest_country', 'housekeeping_notes']);

        return $data;
    }

    private function isDirect(): bool
    {
        $booking = $this->route('booking');

        return $booking instanceof Booking && $booking->source === BookingSource::Direct;
    }
}
