<?php

namespace App\Http\Requests\Admin\Messages;

use Illuminate\Foundation\Http\FormRequest;

/** Suivi d'un message : lu ou non lu. */
class UpdateContactMessageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['read' => ['required', 'boolean']];
    }

    public function read(): bool
    {
        return $this->boolean('read');
    }
}
