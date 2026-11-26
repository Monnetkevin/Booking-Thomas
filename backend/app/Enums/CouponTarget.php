<?php

namespace App\Enums;

/** Ce que vise un code : l'hébergement avec le ménage, une option précise, ou les deux. */
enum CouponTarget: string
{
    case Stay = 'stay';
    case Extra = 'extra';
    case Both = 'both';
}
