<?php

namespace App\Http\Requests\Admin\Search;

use Illuminate\Foundation\Http\FormRequest;

/** Recherche rapide du back-office : 2 à 100 caractères. */
class SearchRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['q' => trim((string) $this->input('q'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['q' => ['required', 'string', 'min:2', 'max:100']];
    }

    public function term(): string
    {
        return $this->string('q')->toString();
    }
}
