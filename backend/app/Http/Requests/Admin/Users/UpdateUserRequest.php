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
        /** @var array{name?: string, is_active?: bool} */
        return $this->safe()->only(['name', 'is_active']);
    }
}
