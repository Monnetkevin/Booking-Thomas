<?php

namespace App\Http\Requests\Admin\Apartment;

use App\Enums\ApartmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexApartmentsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['status' => ['nullable', Rule::enum(ApartmentStatus::class)]];
    }

    public function status(): ?ApartmentStatus
    {
        return $this->enum('status', ApartmentStatus::class);
    }
}
