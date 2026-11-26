<?php

namespace App\Services\Ical;

/**
 * Contenu lu d'un flux iCal : les séjours, et les UID des événements présents mais dont
 * les dates sont illisibles (ni annulés, ni disparus : on ne sait rien de leurs nuits).
 */
final readonly class ParsedCalendar
{
    /**
     * @param  list<ImportedEvent>  $events  un événement par UID
     * @param  list<string>  $unreadableUids
     */
    public function __construct(
        public array $events,
        public array $unreadableUids = [],
    ) {}
}
