<?php

namespace App\Enums;

/** Origine d'une plage de nuits occupées dans availability_blocks. */
enum BlockType: string
{
    case Booking = 'booking';
    case Ical = 'ical';
    case Manual = 'manual';
}
