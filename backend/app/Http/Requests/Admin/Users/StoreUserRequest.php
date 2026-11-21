<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Invitation d'un compte gestionnaire ou ménage : nom, e-mail (unique, sans tenir compte de la casse), rôle. */
class StoreUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')],
            // Jamais « admin » : un administrateur ne se crée pas depuis l'interface.
            'role' => ['sometimes', 'string', Rule::in(UserRole::assignable())],
        ];
    }

    public function role(): UserRole
    {
        return UserRole::from($this->validated('role', UserRole::Housekeeping->value));
    }
}
