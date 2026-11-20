<?php

namespace App\Http\Requests\Admin\Search;

use Illuminate\Foundation\Http\FormRequest;

/** Recherche rapide du back-office : 2 à 100 caractères. */
class SearchRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Un tableau (q[]=…) est laissé tel quel : la règle « string » le refuse (422).
        $q = $this->input('q');
        if (is_string($q)) {
            $this->merge(['q' => trim($q)]);
        }
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
