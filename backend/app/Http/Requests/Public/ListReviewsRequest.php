<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/** Avis publiés : d'un logement (slug) ou de tous, page par page. */
class ListReviewsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apartment' => ['sometimes', 'string', 'regex:/^[a-z0-9-]+$/', 'max:120'],
            'page' => ['sometimes', 'integer', 'between:1,500'],
        ];
    }

    public function apartment(): ?string
    {
        return $this->filled('apartment') ? $this->string('apartment')->toString() : null;
    }

    public function page(): int
    {
        return $this->filled('page') ? $this->integer('page') : 1;
    }
}
