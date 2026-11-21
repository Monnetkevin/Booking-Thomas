<?php

namespace App\Enums;

/**
 * Onglets de la liste des réservations : à venir, en cours, terminées (séjours vendus,
 * selon les dates du jour) et annulées. Les réservations en attente de paiement ou
 * expirées n'apparaissent que dans « Toutes ».
 */
enum BookingStage: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Past = 'past';
    case Cancelled = 'cancelled';
}
