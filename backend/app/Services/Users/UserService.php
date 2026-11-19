<?php

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Exceptions\Users\AccountLockoutException;
use App\Mail\AccountPasswordLink;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Comptes du back-office : le compte ménage est créé par invitation (lien pour choisir son
 * mot de passe, valable 24 h), activé ou désactivé par l'administrateur, qui peut aussi
 * envoyer un lien de réinitialisation. Le mot de passe n'est jamais choisi ni vu par l'admin.
 */
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordBrokerFactory $brokers,
        private readonly Mailer $mailer,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function list(): Collection
    {
        return $this->users->all();
    }

    /** Nouveau compte ménage, actif, sans mot de passe utilisable avant l'invitation. */
    public function invite(string $name, string $email): User
    {
        $user = $this->users->create([
            'name' => $name,
            'email' => $email,
            'password' => Str::password(40),
            'role' => UserRole::Housekeeping,
            'is_active' => true,
        ]);
        $this->sendPasswordLink($user, invitation: true);

        return $user;
    }

    /**
     * @param  array{name?: string, is_active?: bool}  $data
     *
     * @throws AccountLockoutException son propre compte, ou le dernier administrateur actif
     */
    public function update(User $actor, User $user, array $data): User
    {
        return DB::transaction(function () use ($actor, $user, $data): User {
            if (($data['is_active'] ?? true) === false && $user->is_active) {
                if ($actor->is($user)) {
                    throw AccountLockoutException::self();
                }
                if ($user->isAdmin() && $this->users->countActiveAdminsForUpdate() <= 1) {
                    throw AccountLockoutException::lastAdmin();
                }
            }

            return $this->users->update($user, $data);
        });
    }

    public function sendPasswordReset(User $user): void
    {
        $this->sendPasswordLink($user, invitation: false);
    }

    /**
     * Mot de passe choisi depuis le lien reçu par e-mail. Le lien ne sert qu'une fois.
     *
     * @throws ValidationException lien inconnu, expiré ou déjà utilisé, ou compte désactivé
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || ! $user->is_active || ! $this->broker()->tokenExists($user, $token)) {
            throw ValidationException::withMessages(['token' => __('users.password.invalid_link', [], 'fr')]);
        }

        // Nouveau mot de passe : les sessions ouvertes sont fermées à leur prochaine requête
        // (AuthenticateSession de Sanctum) et les cookies « se souvenir de moi » ne valent plus.
        $this->users->updatePassword($user, $password);
        $this->broker()->deleteToken($user);
    }

    private function sendPasswordLink(User $user, bool $invitation): void
    {
        $token = $this->broker()->createToken($user);
        $this->mailer->to($user)->queue(new AccountPasswordLink($user, $token, $invitation));
    }

    private function broker(): PasswordBroker
    {
        /** @var PasswordBroker */
        return $this->brokers->broker();
    }
}
