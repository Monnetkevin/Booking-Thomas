<?php

namespace App\Services\Notifications;

use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/** E-mails destinés à l'hôte : adresses du réglage « notifications.admin_emails ». */
class HostNotifier
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    /**
     * @return list<string>
     */
    public function recipients(): array
    {
        return array_values(array_filter((array) $this->settings->get('notifications.admin_emails', [])));
    }

    /**
     * Met l'e-mail en file pour l'hôte ; sans adresse réglée, rien n'est envoyé.
     *
     * @param  list<string>  $except  adresses qui le reçoivent déjà autrement (comptes administrateurs)
     */
    public function notify(Mailable $mail, array $except = []): void
    {
        $except = array_map(mb_strtolower(...), $except);
        $recipients = array_values(array_filter($this->recipients(), fn (string $address) => ! in_array(mb_strtolower($address), $except, true)));
        if ($recipients !== []) {
            Mail::to($recipients)->queue($mail);
        }
    }
}
