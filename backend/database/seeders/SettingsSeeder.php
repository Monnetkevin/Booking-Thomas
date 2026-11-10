<?php

namespace Database\Seeders;

use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Database\Seeder;

/**
 * Valeurs par défaut proposées dans la spec ; toutes modifiables dans Admin › Réglages.
 */
class SettingsSeeder extends Seeder
{
    public function run(SettingRepositoryInterface $settings): void
    {
        $defaults = [
            // Règles de réservation (globales)
            'booking.min_notice_days' => 1,
            'booking.horizon_months' => 12,
            'booking.pending_hold_minutes' => 30,

            // Taxe de séjour : par adulte et par nuit, mineurs exonérés.
            // Montant et mode à confirmer avec la délibération de la commune.
            'tourist_tax' => [
                'mode' => 'per_adult_night',
                'amount' => 0,
                'percentage' => null,
                'cap_per_adult_night' => null,
            ],

            // Barèmes d'annulation : paliers « jours avant l'arrivée → % remboursé », à valider.
            'cancellation_policies' => [
                'flexible' => [
                    ['days_before' => 1, 'refund_percent' => 100],
                    ['days_before' => 0, 'refund_percent' => 0],
                ],
                'moderate' => [
                    ['days_before' => 5, 'refund_percent' => 100],
                    ['days_before' => 0, 'refund_percent' => 50],
                ],
                'strict' => [
                    ['days_before' => 14, 'refund_percent' => 100],
                    ['days_before' => 7, 'refund_percent' => 50],
                    ['days_before' => 0, 'refund_percent' => 0],
                ],
            ],

            // Destinataires des notifications de l'hôte (nouvelle réservation, conflit iCal…)
            'notifications.admin_emails' => [config('booking.admin.email')],
        ];

        foreach ($defaults as $key => $value) {
            $settings->putIfMissing($key, $value);
        }
    }
}
