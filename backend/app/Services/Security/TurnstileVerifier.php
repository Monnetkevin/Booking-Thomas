<?php

namespace App\Services\Security;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile : défi anti-robot invisible pour les visiteurs.
 * Sans clé secrète configurée (développement local), la vérification est désactivée.
 */
class TurnstileVerifier implements HumanVerifier
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly Http $http,
        private readonly ?string $secret,
    ) {}

    public function verify(?string $token, ?string $ip): bool
    {
        if (blank($this->secret)) {
            return true;
        }
        if (blank($token)) {
            return false;
        }

        try {
            $response = $this->http->asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (ConnectionException $e) {
            // Turnstile injoignable : on refuse plutôt que d'ouvrir la porte aux robots.
            Log::warning('Turnstile injoignable', ['error' => $e->getMessage()]);

            return false;
        }

        return $response->json('success') === true;
    }
}
