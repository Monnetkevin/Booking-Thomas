<?php

namespace App\Jobs;

use App\Models\IcalFeed;
use App\Services\Ical\IcalImportService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Relit le calendrier d'une plateforme pour un logement. Une seule synchronisation à la
 * fois par flux ; les nouvelles tentatives sont faites par le téléchargement lui-même.
 */
class SyncIcalFeed implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** Le verrou d'unicité tombe au plus tard à la synchronisation suivante. */
    public int $uniqueFor = 900;

    public function __construct(public readonly IcalFeed $feed) {}

    public function uniqueId(): string
    {
        return (string) $this->feed->id;
    }

    public function handle(IcalImportService $imports): void
    {
        // Désactivé ou URL retirée depuis la mise en file : plus rien à relire.
        if ($this->feed->is_active && filled($this->feed->import_url)) {
            $imports->sync($this->feed);
        }
    }
}
