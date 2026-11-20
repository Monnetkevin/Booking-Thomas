<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $email
 * @property UserRole $role
 * @property bool $is_active
 * @property array<string, mixed>|null $notification_preferences e-mail par type, récapitulatif du ménage
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'notification_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * E-mails enregistrés en minuscules : la connexion ne dépend pas de la casse.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => mb_strtolower(trim($value)));
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Administrateur dont le compte est actif : seul autorisé à gérer le site. */
    public function isActiveAdmin(): bool
    {
        return $this->is_active && $this->isAdmin();
    }

    /**
     * Compte actif qui gère l'activité (administrateur ou gestionnaire) : réservations,
     * planning, logements, calendriers, messages. Réglages, utilisateurs et tarifs
     * restent à l'administrateur (isActiveAdmin).
     */
    public function canOperate(): bool
    {
        return $this->is_active && in_array($this->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function isHousekeeping(): bool
    {
        return $this->role === UserRole::Housekeeping;
    }

    /**
     * Notifications du back-office (modèle propre au site : niveau, regroupement, purge).
     *
     * @return MorphMany<Notification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }
}
