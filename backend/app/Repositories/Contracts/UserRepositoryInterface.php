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

    /**
     * @param  list<UserRole>  $roles
     * @return Collection<int, User>
     */
    public function activeByRoles(array $roles): Collection;

    /** Nombre d'administrateurs actifs, lignes verrouillées jusqu'à la fin de la transaction. */
    public function countActiveAdminsForUpdate(): int;

    /** Note la connexion réussie (sans toucher à updated_at). */
    public function touchLastLogin(User $user): void;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User;

    /** Nouveau mot de passe et nouveau jeton « se souvenir de moi » (les anciens cookies ne valent plus). */
    public function updatePassword(User $user, string $password): User;
}
