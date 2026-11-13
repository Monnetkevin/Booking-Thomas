<?php

namespace App\Services\Availability;

use App\Models\AvailabilityBlock;
use Illuminate\Database\Eloquent\Collection;

/** Blocs actifs (hors conflit) qui occupent déjà des nuits demandées par un nouveau bloc. */
final readonly class BlockOverlap
{
    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks
     */
    public function __construct(public Collection $blocks) {}

    /** Le nouveau bloc doit être enregistré en conflit (hors contrainte d'exclusion). */
    public function isConflict(): bool
    {
        return $this->blocks->isNotEmpty();
    }
}
