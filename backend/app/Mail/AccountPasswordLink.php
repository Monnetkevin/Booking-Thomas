<?php

namespace App\Mail;

use App\Models\User;
use App\Support\SiteUrl;

/**
 * Lien pour choisir son mot de passe : invitation d'un nouveau compte, ou réinitialisation
 * demandée par l'administrateur. Valable 24 heures.
 */
class AccountPasswordLink extends TransactionalMail
{
    public function __construct(
        public readonly User $user,
        private readonly string $token,
        public readonly bool $invitation,
    ) {
        $this->prepare('fr');
    }

    protected function markdownView(): string
    {
        return 'mail.account-password';
    }

    protected function subjectLine(): string
    {
        return $this->invitation ? __('mail.account.invite_subject') : __('mail.account.reset_subject');
    }

    protected function reason(): string
    {
        return __('mail.account.reason');
    }

    protected function viewData(): array
    {
        return [
            'name' => $this->user->name,
            'invitation' => $this->invitation,
            'url' => SiteUrl::to('/admin/mot-de-passe?'.http_build_query(['token' => $this->token, 'email' => $this->user->email])),
        ];
    }
}
