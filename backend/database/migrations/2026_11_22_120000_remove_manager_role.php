<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rôle Gestionnaire retiré (décision du 2026-11-22) : un compte gestionnaire devient un compte
 * ménage désactivé. Il n'est jamais promu administrateur ; l'administrateur le réactive ou le
 * supprime s'il le faut.
 *
 * Même nettoyage qu'un changement de rôle (UserService::update) : les notifications reçues comme
 * gestionnaire (noms, coordonnées, montants) sont supprimées et les préférences remises à zéro,
 * pour qu'une simple réactivation ne les rende pas visibles au compte ménage.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $ids = DB::table('users')->where('role', 'manager')->pluck('id');
            if ($ids->isEmpty()) {
                return;
            }

            // Types du ménage à la date de la migration, écrits en dur : la migration doit donner
            // le même résultat même si NotificationType change plus tard.
            $kept = ['stay_changed', 'daily_summary'];
            DB::table('notifications')
                ->where('notifiable_type', (new User)->getMorphClass())
                ->whereIn('notifiable_id', $ids)
                ->whereNotIn('type', $kept)
                ->delete();

            DB::table('users')->whereIn('id', $ids)->update([
                'role' => 'housekeeping',
                'is_active' => false,
                'notification_preferences' => null,
            ]);
        });
    }

    public function down(): void
    {
        // Rien à restaurer : on ne sait plus quels comptes étaient gestionnaires.
    }
};
