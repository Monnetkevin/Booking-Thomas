<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Nom d'un compte, activation ou désactivation, rôle (gestionnaire ou ménage). */
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
            'role' => ['sometimes', 'string', Rule::in(UserRole::assignable())],
        ];
    }

    /**
     * @return array{name?: string, is_active?: bool, role?: UserRole}
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
        if ($this->has('role')) {
            $data['role'] = UserRole::from($this->string('role')->toString());
        }

        return $data;
    }
}
