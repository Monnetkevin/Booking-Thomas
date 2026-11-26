<?php

namespace App\Enums;

/** Pourcentage, montant fixe, ou option offerte (son prix passe à 0 € pour la quantité indiquée). */
enum CouponType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeExtra = 'free_extra';
}
