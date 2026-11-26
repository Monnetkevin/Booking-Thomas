<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', mb_strtolower(trim($email)))->first();
    }

    public function all(): Collection
    {
        // Administrateurs, puis ménage ; par nom dans chaque rôle.
        return User::query()
            ->orderByRaw('case role when ? then 0 else 1 end', [UserRole::Admin->value])
            ->orderBy('name')
            ->get();
    }

    public function activeByRole(UserRole $role): Collection
    {
        return User::query()->where('role', $role->value)->where('is_active', true)->get();
    }

    public function activeByRoles(array $roles): Collection
    {
        return User::query()->whereIn('role', array_map(fn (UserRole $role) => $role->value, $roles))->where('is_active', true)->get();
    }

    public function countActiveAdminsForUpdate(): int
    {
        // PostgreSQL refuse FOR UPDATE avec un agrégat : on verrouille les lignes, puis on compte.
        return count(User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->lockForUpdate()->pluck('id')->all());
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

    public function touchLastLogin(User $user): void
    {
        $user->timestamps = false;
        $user->forceFill(['last_login_at' => now()])->save();
        $user->timestamps = true;
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        return $user;
    }
}
