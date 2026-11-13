<?php

namespace App\Enums;

/** Issue d'une synchronisation d'un flux iCal. */
enum IcalSyncStatus: string
{
    /** Flux lu et appliqué. */
    case Success = 'success';
    /** Flux identique au précédent (réponse 304 ou même contenu) : rien à faire. */
    case Unchanged = 'unchanged';
    /** Erreur : les nuits déjà connues sont conservées telles quelles. */
    case Failed = 'failed';
}
