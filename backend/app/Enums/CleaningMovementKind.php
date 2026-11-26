<?php

namespace App\Enums;

/**
 * Ce que le ménage doit savoir d'un jour donné pour un logement : un départ (ménage à
 * faire), une arrivée (logement prêt avant l'heure), une fermeture qui commence, ou
 * l'occupation par des proches (début, puis fin avec ménage).
 */
enum CleaningMovementKind: string
{
    case Departure = 'departure';
    case Arrival = 'arrival';
    case Blocked = 'blocked';
    case Occupied = 'occupied';
    case OccupiedEnd = 'occupied_end';
}
