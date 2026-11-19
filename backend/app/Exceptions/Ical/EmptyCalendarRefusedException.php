<?php

namespace App\Exceptions\Ical;

/**
 * Flux soudain vide alors que des nuits étaient bloquées : refusé par précaution. L'hôte
 * peut confirmer qu'il est bien vide chez la plateforme pour libérer ces nuits.
 */
class EmptyCalendarRefusedException extends IcalSyncException
{
    public function __construct()
    {
        parent::__construct(__('ical.errors.suspicious_empty', [], 'fr'));
    }
}
