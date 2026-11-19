<?php

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;

/** Nom d'un compte, activation ou désactivation. */
class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{name?: string, is_active?: bool}
     */
    public function accountData(): array
    {
        // Booléens stricts : « 0 » ou "false" ne doivent pas contourner les garde-fous du service.
        $data = [];
        if ($this->has('name')) {
            $data['name'] = $this->string('name')->toString();
        }
        if ($this->has('is_active')) {
            $data['is_active'] = $this->boolean('is_active');
        }

        return $data;
    }
}
