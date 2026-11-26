<?php

namespace App\Http\Requests\Admin\Bookings;

use App\Enums\BookingSource;
use App\Enums\BookingStage;
use App\Enums\BookingStatus;
use App\Services\Booking\BookingFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filtres de la liste des réservations. */
class ListBookingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(BookingStatus::class)],
            'apartment_id' => ['sometimes', 'nullable', 'integer', Rule::exists('apartments', 'id')],
            'source' => ['sometimes', 'nullable', Rule::enum(BookingSource::class)],
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after:from'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'stage' => ['sometimes', 'nullable', Rule::enum(BookingStage::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function filters(): BookingFilters
    {
        $search = trim($this->string('search')->toString());

        return new BookingFilters(
            status: BookingStatus::tryFrom($this->string('status')->toString()),
            apartmentId: $this->filled('apartment_id') ? $this->integer('apartment_id') : null,
            source: BookingSource::tryFrom($this->string('source')->toString()),
            from: $this->filled('from') ? $this->string('from')->toString() : null,
            to: $this->filled('to') ? $this->string('to')->toString() : null,
            search: $search === '' ? null : $search,
            stage: BookingStage::tryFrom($this->string('stage')->toString()),
            today: now()->toDateString(),
        );
    }
}
