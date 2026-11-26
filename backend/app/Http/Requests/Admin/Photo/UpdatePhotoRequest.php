<?php

namespace App\Http\Requests\Admin\Photo;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
{
    /**
     * Texte alternatif lu par les lecteurs d'écran et les moteurs de recherche.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alt' => ['required', 'array'],
            'alt.fr' => ['nullable', 'string', 'max:200'],
            'alt.en' => ['nullable', 'string', 'max:200'],
        ];
    }
}
