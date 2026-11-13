<?php

namespace App\Enums;

/** Origine d'une synchronisation, qui fixe sa patience envers la plateforme. */
enum IcalSyncTrigger: string
{
    /** Tâche planifiée toutes les 15 minutes : 10 secondes, 3 tentatives (spec). */
    case Scheduled = 'scheduled';
    /** Bouton « Synchroniser maintenant » : l'hôte attend la réponse, une seule tentative. */
    case Manual = 'manual';

    public function timeoutSeconds(): int
    {
        return 10;
    }

    public function attempts(): int
    {
        return match ($this) {
            self::Scheduled => 3,
            self::Manual => 1,
        };
    }
}
