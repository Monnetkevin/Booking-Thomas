<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rôle Gestionnaire retiré (décision du 2026-11-22) : un compte gestionnaire devient un compte
 * ménage désactivé. Il n'est jamais promu administrateur ; l'administrateur le réactive ou le
 * supprime s'il le faut.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'manager')->update(['role' => 'housekeeping', 'is_active' => false]);
    }

    public function down(): void
    {
        // Rien à restaurer : on ne sait plus quels comptes étaient gestionnaires.
    }
};
