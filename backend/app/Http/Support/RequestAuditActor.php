<?php

namespace App\Http\Support;

use App\Models\User;
use App\Services\Audit\AuditActor;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

/** Auteur lu dans la requête HTTP en cours ; personne hors requête (console, file d'attente). */
class RequestAuditActor implements AuditActor
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly Container $container,
    ) {}

    public function user(): ?User
    {
        $user = $this->auth->guard('web')->user();

        return $user instanceof User ? $user : null;
    }

    public function ip(): ?string
    {
        if (! $this->container->bound('request')) {
            return null;
        }
        $request = $this->container->make(Request::class);

        // Hors d'une route (console, file d'attente), il n'y a pas d'adresse à noter.
        return $request->route() !== null ? $request->ip() : null;
    }
}
