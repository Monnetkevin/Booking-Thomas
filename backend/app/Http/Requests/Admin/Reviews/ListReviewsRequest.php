<?php

namespace App\Http\Requests\Admin\Reviews;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** File de modération : par statut et par logement. */
class ListReviewsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ReviewStatus::class)],
            'apartment_id' => ['sometimes', 'integer', Rule::exists('apartments', 'id')],
        ];
    }

    public function status(): ?ReviewStatus
    {
        return $this->filled('status') ? ReviewStatus::from($this->string('status')->toString()) : null;
    }

    public function apartmentId(): ?int
    {
        return $this->filled('apartment_id') ? $this->integer('apartment_id') : null;
    }
}
