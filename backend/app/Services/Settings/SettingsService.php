<?php

namespace App\Services\Settings;

use App\Enums\AuditAction;
use App\Enums\CancellationPolicy;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\CatalogCache;

/**
 * Réglages de l'hôte (spec « Back-office › Réglages ») : préavis et horizon de
 * réservation, taxe de séjour, barèmes d'annulation, adresses des e-mails de
 * notification. Chaque modification est journalisée ; les pages publiques (prix,
 * calendriers, conditions) sont recalculées. Un barème changé ne touche pas les
 * réservations existantes, qui en gardent une copie.
 */
class SettingsService
{
    /** Réglages modifiables depuis le back-office (clé d'API → clé en base). */
    private const KEYS = [
        'min_notice_days' => 'booking.min_notice_days',
        'horizon_months' => 'booking.horizon_months',
        'tourist_tax' => 'tourist_tax',
        'cancellation_policies' => 'cancellation_policies',
        'notification_emails' => 'notifications.admin_emails',
    ];

    public function __construct(
        private readonly SettingRepositoryInterface $settings,
        private readonly AuditLogger $audit,
        private readonly CatalogCache $catalog,
    ) {}

    /**
     * @return array{min_notice_days: int, horizon_months: int, tourist_tax: array<string, mixed>, cancellation_policies: array<string, list<array{days_before: int, refund_percent: int}>>, notification_emails: list<string>}
     */
    public function current(): array
    {
        $policies = (array) $this->settings->get('cancellation_policies', []);

        return [
            'min_notice_days' => (int) $this->settings->get('booking.min_notice_days', 1),
            'horizon_months' => (int) $this->settings->get('booking.horizon_months', 12),
            'tourist_tax' => array_merge(
                ['mode' => 'per_adult_night', 'amount' => 0, 'percentage' => null, 'cap_per_adult_night' => null],
                (array) $this->settings->get('tourist_tax', []),
            ),
            'cancellation_policies' => array_combine(
                array_column(CancellationPolicy::cases(), 'value'),
                array_map(fn (CancellationPolicy $policy) => array_values((array) ($policies[$policy->value] ?? [])), CancellationPolicy::cases()),
            ),
            'notification_emails' => array_values(array_filter((array) $this->settings->get('notifications.admin_emails', []))),
        ];
    }

    /**
     * Enregistre les réglages envoyés (déjà validés) ; seuls ceux qui changent sont écrits
     * et journalisés.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> réglages à jour
     */
    public function update(array $data): array
    {
        $current = $this->current();
        $before = $after = [];

        foreach (self::KEYS as $field => $key) {
            if (! array_key_exists($field, $data) || $data[$field] === $current[$field]) {
                continue;
            }
            $this->settings->put($key, $data[$field]);
            $before[$field] = $current[$field];
            $after[$field] = $data[$field];
        }

        if ($after !== []) {
            $this->audit->record(AuditAction::SettingsUpdated, before: $before, after: $after);
            // Prix, taxe, calendriers et conditions affichés sur le site en dépendent.
            $this->catalog->flush();
        }

        return $this->current();
    }
}
