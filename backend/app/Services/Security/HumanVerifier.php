<?php

namespace App\Services\Security;

interface HumanVerifier
{
    /** Le jeton du défi anti-robot envoyé par le navigateur est-il valide ? */
    public function verify(?string $token, ?string $ip): bool;
}
