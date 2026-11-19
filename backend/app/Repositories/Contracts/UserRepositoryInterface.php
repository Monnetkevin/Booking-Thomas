<?php

namespace App\Repositories\Contracts;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * Comptes du back-office : administrateurs d'abord, puis par nom.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection;

    /**
     * Comptes actifs d'un rôle : destinataires des notifications.
     *
     * @return Collection<int, User>
     */
    public function activeByRole(UserRole $role): Collection;

    public function countActiveAdmins(): int;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User;
}
