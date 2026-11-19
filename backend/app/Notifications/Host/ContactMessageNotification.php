<?php

namespace App\Notifications\Host;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Models\ContactMessage;
use App\Notifications\BackOfficeNotification;
use Illuminate\Support\Str;

/** Nouveau message du formulaire de contact ; l'e-mail complet part aux adresses du réglage. */
class ContactMessageNotification extends BackOfficeNotification
{
    public function __construct(public readonly ContactMessage $message)
    {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::ContactMessage;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Info;
    }

    public function title(): string
    {
        return __('notifications.contact_message.title', ['name' => $this->message->name], 'fr');
    }

    public function body(): string
    {
        return __('notifications.contact_message.body', ['excerpt' => Str::limit(Str::squish($this->message->message), 160)], 'fr');
    }

    public function link(): string
    {
        return '/admin';
    }
}
