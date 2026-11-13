<?php

namespace App\Services\Ical;

use Carbon\CarbonImmutable;

/**
 * Événement lu dans un flux iCal : nuits [startsOn, endsOn[ et identifiant stable (UID).
 */
final readonly class ImportedEvent
{
    public function __construct(
        public string $uid,
        public CarbonImmutable $startsOn,
        public CarbonImmutable $endsOn,
        public ?string $summary = null,
        public ?string $url = null,
    ) {}
}
