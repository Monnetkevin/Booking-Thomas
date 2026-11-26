<?php

namespace App\Http\Requests\Public;

use App\Services\Booking\Stay;
use Illuminate\Foundation\Http\FormRequest;

/** Réservation d'un séjour par un voyageur : séjour, coordonnées et acceptation des CGV. */
class StoreBookingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('country')) {
            $this->merge(['country' => strtoupper(trim($this->string('country')->toString()))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment' => ['required', 'string', 'max:100'],
            ...QuoteRequest::stayRules(),
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            // Code pays ISO 3166-1 alpha-2 (« FR », « GB »).
            'country' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],
            'message' => ['nullable', 'string', 'max:2000'],
            'accept_terms' => ['accepted'],
            'captcha_token' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function stay(): Stay
    {
        return Stay::fromStrings(
            $this->string('arrival')->toString(),
            $this->string('departure')->toString(),
            $this->integer('adults'),
            $this->integer('children'),
        );
    }

    /**
     * @return array{name: string, email: string, phone: string, country: string, message?: string|null}
     */
    public function guest(): array
    {
        /** @var array{name: string, email: string, phone: string, country: string, message?: string|null} */
        return $this->safe()->only(['name', 'email', 'phone', 'country', 'message']);
    }
}
