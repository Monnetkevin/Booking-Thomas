<?php

namespace App\Enums;

enum BookingSource: string
{
    /** Réservée et payée sur le site. */
    case Direct = 'direct';
    /** Saisie par l'hôte (téléphone, connaissance). */
    case Manual = 'manual';
}
