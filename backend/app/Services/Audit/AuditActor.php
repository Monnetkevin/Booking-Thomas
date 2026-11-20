<?php

namespace App\Services\Audit;

use App\Models\User;

/**
 * Auteur d'une action journalisée : le compte connecté et son adresse IP, ou personne
 * (tâche planifiée, ligne de commande). Les services en dépendent sans toucher à HTTP.
 */
interface AuditActor
{
    public function user(): ?User;

    public function ip(): ?string;
}
