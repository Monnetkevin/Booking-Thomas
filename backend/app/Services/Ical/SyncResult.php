<?php

namespace App\Services\Ical;

use App\Enums\IcalSyncStatus;

/** Bilan d'une synchronisation : nuits ajoutées, déplacées, libérées, conflits en cours. */
final readonly class SyncResult
{
    public function __construct(
        public IcalSyncStatus $status,
        public int $created = 0,
        public int $updated = 0,
        public int $removed = 0,
        public int $conflicts = 0,
        public ?string $error = null,
    ) {}

    public static function unchanged(): self
    {
        return new self(IcalSyncStatus::Unchanged);
    }

    public static function failed(string $error): self
    {
        return new self(IcalSyncStatus::Failed, error: $error);
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
