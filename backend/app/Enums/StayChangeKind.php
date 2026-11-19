<?php

namespace App\Enums;

/** Ce qui change au planning du ménage : un séjour ajouté, annulé ou déplacé. */
enum StayChangeKind: string
{
    case Added = 'added';
    case Cancelled = 'cancelled';
    case Moved = 'moved';
}
