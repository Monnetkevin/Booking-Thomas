<?php

namespace App\Http\Requests\Admin\Bookings;

use App\Services\Booking\Stay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Réservation saisie par l'hôte : logement, séjour, voyageur (e-mail facultatif), total
 * fixé en centimes si besoin, règlement reçu hors ligne, confirmation au voyageur.
 */
class StoreManualBookingRequest extends FormRequest
{
    public const MAX_NIGHTS = 90;

    protected function prepareForValidation(): void
    {
        foreach (['guest_name', 'guest_email', 'guest_phone', 'guest_country', 'housekeeping_notes'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
        if (is_string($this->input('guest_email'))) {
            $this->merge(['guest_email' => mb_strtolower($this->input('guest_email'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment_id' => ['required', 'integer', Rule::exists('apartments', 'id')->whereNull('deleted_at')],
            'arrival' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'departure' => ['required', 'date_format:Y-m-d', 'after:arrival'],
            'adults' => ['required', 'integer', 'between:1,30'],
            'children' => ['sometimes', 'integer', 'between:0,30'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['nullable', 'email', 'max:190'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_country' => ['nullable', 'string', 'size:2', 'alpha'],
            'locale' => ['sometimes', Rule::in(['fr', 'en'])],
            'housekeeping_notes' => ['nullable', 'string', 'max:500'],
            'total_amount' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'paid' => ['required', 'boolean'],
            'send_confirmation' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        // Un total à 0 € : le séjour se saisit comme une fermeture de dates.
        return ['total_amount.min' => __('booking.errors.free_stay', [], 'fr')];
    }

    /**
     * 90 nuits au plus, vérifié une fois les dates valides (jamais d'analyse d'une date
     * impossible avant la validation).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('arrival') || $validator->errors()->has('departure')) {
                return;
            }
            if ($this->stay()->nights() > self::MAX_NIGHTS) {
                $validator->errors()->add('departure', __('validation.max.numeric', ['attribute' => 'nuits', 'max' => self::MAX_NIGHTS]));
            }
        }];
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
     * @return array{name: string, email: string|null, phone: string|null, country: string|null, locale: string, housekeeping_notes: string|null}
     */
    public function guest(): array
    {
        return [
            'name' => (string) $this->validated('guest_name'),
            'email' => $this->validated('guest_email'),
            'phone' => $this->validated('guest_phone'),
            'country' => $this->filled('guest_country') ? mb_strtoupper((string) $this->validated('guest_country')) : null,
            'locale' => (string) ($this->validated('locale') ?? 'fr'),
            'housekeeping_notes' => $this->validated('housekeeping_notes'),
        ];
    }

    public function total(): ?int
    {
        return $this->filled('total_amount') ? $this->integer('total_amount') : null;
    }
}
