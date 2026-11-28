<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Connexion des comptes du back-office (session « web » utilisée par Sanctum).
 */
class AuthService
{
    /** Tentatives autorisées par e-mail et adresse IP avant blocage temporaire. */
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    /**
     * Échecs autorisés sur un même compte, toutes adresses IP confondues : un attaquant qui
     * change d'adresse ne peut pas essayer des mots de passe sans fin.
     */
    public const MAX_ATTEMPTS_PER_ACCOUNT = 20;

    public const ACCOUNT_DECAY_SECONDS = 900;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly StatefulGuard $guard,
        private readonly Hasher $hasher,
    ) {}

    /**
     * Connecte un compte actif. Le message d'erreur est le même que l'e-mail existe ou non.
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, bool $remember, ?string $ip): User
    {
        $throttleKey = $this->throttleKey($email, $ip);
        $accountKey = 'compte|'.$this->throttleKey($email, null);
        $this->ensureIsNotRateLimited($throttleKey, self::MAX_ATTEMPTS);
        $this->ensureIsNotRateLimited($accountKey, self::MAX_ATTEMPTS_PER_ACCOUNT);

        $user = $this->users->findByEmail($email);
        // Même coût de hachage que le compte existe ou non : le temps de réponse ne trahit rien.
        $passwordMatches = $this->hasher->check($password, $user->password ?? $this->dummyHash());

        if (! $user || ! $user->is_active || ! $passwordMatches) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            RateLimiter::hit($accountKey, self::ACCOUNT_DECAY_SECONDS);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);
        RateLimiter::clear($accountKey);
        $this->guard->login($user, $remember);
        $this->users->touchLastLogin($user);

        return $user;
    }

    private function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= $this->hasher->make('mot-de-passe-factice');
    }

    public function logout(): void
    {
        $this->guard->logout();
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(string $throttleKey, int $maxAttempts): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
        ])->status(429);
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return Str::transliterate(Str::lower($email).'|'.$ip);
    }
}
