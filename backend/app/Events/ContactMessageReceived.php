<?php

namespace App\Events;

use App\Models\ContactMessage;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un visiteur vient d'écrire depuis le formulaire de contact. */
class ContactMessageReceived implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly ContactMessage $message) {}
}
