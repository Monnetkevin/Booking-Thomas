<?php

namespace App\Services\Contact;

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use App\Services\Notifications\HostNotifier;
use App\Services\Security\HumanVerifier;
use Illuminate\Validation\ValidationException;

/**
 * Formulaire de contact : défi anti-robot, enregistrement pour le back-office et
 * e-mail à l'hôte.
 */
class ContactService
{
    public function __construct(
        private readonly ContactMessageRepositoryInterface $messages,
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly HostNotifier $host,
        private readonly HumanVerifier $humans,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null, message: string, apartment?: string|null, website?: string|null}  $data  apartment = slug du logement concerné
     *
     * @throws ValidationException défi anti-robot échoué
     */
    public function send(array $data, ?string $captchaToken, ?string $ip, string $locale): ?ContactMessage
    {
        // Champ piège rempli : un robot. On fait comme si tout allait bien, sans rien garder.
        if (filled($data['website'] ?? null)) {
            return null;
        }

        if (! $this->humans->verify($captchaToken, $ip)) {
            throw ValidationException::withMessages(['captcha' => __('contact.captcha_failed')]);
        }

        $apartmentId = filled($data['apartment'] ?? null) ? $this->apartments->idForSlug((string) $data['apartment']) : null;
        unset($data['website'], $data['apartment']);
        $message = $this->messages->create([...$data, 'apartment_id' => $apartmentId, 'locale' => $locale]);

        $this->host->notify(new NewContactMessage($message));

        return $message;
    }
}
