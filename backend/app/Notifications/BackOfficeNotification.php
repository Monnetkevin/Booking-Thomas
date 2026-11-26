<?php

namespace App\Notifications;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Mail\NotificationMail;
use App\Models\User;
use App\Notifications\Channels\InAppChannel;
use App\Services\Notifications\NotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base des notifications du back-office : toujours dans le back-office ; par e-mail si
 * elle est urgente et que le compte le veut (ou que le type l'impose).
 *
 * Une notification fournit son type, son contenu figé (titre, texte, lien) et, au besoin,
 * une clé d'objet : une notification non lue du même type et du même objet est
 * rafraîchie au lieu d'être répétée. Envoyée en file d'attente, après la transaction.
 */
abstract class BackOfficeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract public function type(): NotificationType;

    abstract public function level(): NotificationLevel;

    abstract public function title(): string;

    abstract public function body(): string;

    /** Page du back-office concernée (chemin relatif, ex. « /admin/synchronisation »). */
    public function link(): ?string
    {
        return null;
    }

    /**
     * Lignes de détail (récapitulatif), affichées sous le texte.
     *
     * @return list<string>
     */
    public function details(): array
    {
        return [];
    }

    /** Objet concerné (calendrier, séjour) : null pour ne jamais regrouper. */
    public function dedupeKey(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        $preferences = NotificationPreferences::fromStored($notifiable->role, $notifiable->notification_preferences);
        $byEmail = $this->level() === NotificationLevel::Urgent || $this->type() === NotificationType::DailySummary;

        return $byEmail && $preferences->wantsEmail($this->type()) ? [InAppChannel::class, 'mail'] : [InAppChannel::class];
    }

    /** E-mail : par défaut le titre, le texte et le lien ; un type peut fournir le sien. */
    public function toMail(User $notifiable): Mailable
    {
        return (new NotificationMail($this->title(), $this->body(), $this->details(), $this->link()))->to($notifiable->email, $notifiable->name);
    }

    /**
     * @return array{title: string, body: string, link: string|null, details: list<string>}
     */
    public function toInApp(): array
    {
        return ['title' => $this->title(), 'body' => $this->body(), 'link' => $this->link(), 'details' => $this->details()];
    }
}
