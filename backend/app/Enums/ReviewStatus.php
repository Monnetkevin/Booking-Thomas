<?php

namespace App\Enums;

/** invited → pending (avis déposé) → published | rejected ; l'hôte peut revenir sur sa décision. */
enum ReviewStatus: string
{
    case Invited = 'invited';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    /** Avis déposé par le voyageur (plus seulement une invitation). */
    public function isSubmitted(): bool
    {
        return $this !== self::Invited;
    }
}
