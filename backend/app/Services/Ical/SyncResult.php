<?php

namespace App\Services\Ical;

use App\Enums\IcalSyncStatus;

/**
 * Bilan d'une synchronisation : nuits ajoutées, déplacées, libérées, conflits en cours,
 * événements aux dates illisibles (laissés tels quels, à signaler à l'hôte).
 */
final readonly class SyncResult
{
    public function __construct(
        public IcalSyncStatus $status,
        public int $created = 0,
        public int $updated = 0,
        public int $removed = 0,
        public int $conflicts = 0,
        public ?string $error = null,
        public int $unreadable = 0,
        public bool $emptyRefused = false,
    ) {}

    public static function unchanged(): self
    {
        return new self(IcalSyncStatus::Unchanged);
    }

    /**
     * @param  bool  $emptyRefused  échec dû à un calendrier vide refusé par précaution
     */
    public static function failed(string $error, bool $emptyRefused = false): self
    {
        return new self(IcalSyncStatus::Failed, error: $error, emptyRefused: $emptyRefused);
    }

    /**
     * Ce que la lecture dit du refus de calendrier vide : true refusé, false levé (lecture
     * aboutie), null inconnu (autre panne : le refus en attente est conservé).
     */
    public function emptyRefusal(): ?bool
    {
        return match (true) {
            $this->emptyRefused => true,
            $this->failedSync() => null,
            default => false,
        };
    }

    public function failedSync(): bool
    {
        return $this->status === IcalSyncStatus::Failed;
    }

    public function hasChanges(): bool
    {
        return $this->created + $this->updated + $this->removed > 0;
    }
}
