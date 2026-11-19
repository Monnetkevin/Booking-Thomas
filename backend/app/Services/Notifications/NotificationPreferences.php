<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Enums\UserRole;

/**
 * Préférences de notification d'un compte : e-mail par type, récapitulatif du ménage.
 * Ce qui n'est pas réglé prend la valeur par défaut du type ; un e-mail obligatoire
 * part toujours.
 */
final readonly class NotificationPreferences
{
    public const DEFAULT_SUMMARY_HOUR = 18;

    public const SUMMARY_HOURS = [6, 22];

    /**
     * @param  array<string, bool>  $email  type => e-mail voulu
     */
    public function __construct(
        private UserRole $role,
        private array $email = [],
        public bool $summaryEnabled = true,
        public int $summaryHour = self::DEFAULT_SUMMARY_HOUR,
    ) {}

    /**
     * @param  array<string, mixed>|null  $stored  valeur de users.notification_preferences
     */
    public static function fromStored(UserRole $role, ?array $stored): self
    {
        $email = [];
        foreach ((array) ($stored['email'] ?? []) as $type => $wanted) {
            if (NotificationType::tryFrom((string) $type) !== null) {
                $email[(string) $type] = (bool) $wanted;
            }
        }

        return new self(
            $role,
            $email,
            (bool) ($stored['summary']['enabled'] ?? true),
            (int) ($stored['summary']['hour'] ?? self::DEFAULT_SUMMARY_HOUR),
        );
    }

    public function wantsEmail(NotificationType $type): bool
    {
        if (! $type->emailable()) {
            return false;
        }

        return $type->emailForced() || ($this->email[$type->value] ?? $type->emailByDefault());
    }

    /**
     * Préférences modifiées ; seuls les types du rôle et ceux qu'on peut couper comptent.
     *
     * @param  array<string, bool>  $email
     */
    public function with(array $email, ?bool $summaryEnabled = null, ?int $summaryHour = null): self
    {
        $allowed = array_column(array_filter(
            NotificationType::for($this->role),
            fn (NotificationType $type) => $type->emailable() && ! $type->emailForced(),
        ), 'value');

        return new self(
            $this->role,
            [...$this->email, ...array_intersect_key($email, array_flip($allowed))],
            $summaryEnabled ?? $this->summaryEnabled,
            $summaryHour ?? $this->summaryHour,
        );
    }

    /**
     * @return array{email: array<string, bool>, summary: array{enabled: bool, hour: int}}
     */
    public function toStored(): array
    {
        return ['email' => $this->email, 'summary' => ['enabled' => $this->summaryEnabled, 'hour' => $this->summaryHour]];
    }

    /**
     * Vue complète pour l'écran des préférences : chaque type du rôle, son état et s'il se règle.
     *
     * @return list<array{type: string, email: bool, editable: bool}>
     */
    public function types(): array
    {
        return array_map(fn (NotificationType $type) => [
            'type' => $type->value,
            'email' => $this->wantsEmail($type),
            'editable' => $type->emailable() && ! $type->emailForced(),
        ], NotificationType::for($this->role));
    }
}
