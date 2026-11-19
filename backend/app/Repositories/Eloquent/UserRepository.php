<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', mb_strtolower(trim($email)))->first();
    }

    public function all(): Collection
    {
        return User::query()->orderByRaw('role = ? desc', [UserRole::Admin->value])->orderBy('name')->get();
    }

    public function activeByRole(UserRole $role): Collection
    {
        return User::query()->where('role', $role->value)->where('is_active', true)->get();
    }

    public function countActiveAdmins(): int
    {
        return User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->count();
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }
}
