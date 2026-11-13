<?php

use App\Exceptions\Ical\IcalSyncException;
use App\Services\Ical\IcalParser;

/** Calendrier minimal autour de quelques lignes VEVENT. */
function calendarWith(string ...$events): string
{
    $body = implode("\r\n", array_map(fn (string $event) => "BEGIN:VEVENT\r\n$event\r\nEND:VEVENT", $events));

    return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//FR\r\n$body\r\nEND:VCALENDAR\r\n";
}

/** @return list<array{0: string, 1: string, 2: string}> [UID, arrivée, départ] */
function parsed(string $content): array
{
    return array_map(
        fn ($event) => [$event->uid, $event->startsOn->toDateString(), $event->endsOn->toDateString()],
        app(IcalParser::class)->parse($content),
    );
}

it('lit des journées entières : départ exclu', function () {
    expect(parsed(calendarWith("UID:a\r\nDTSTART;VALUE=DATE:20261121\r\nDTEND;VALUE=DATE:20261125")))
        ->toBe([['a', '2026-11-21', '2026-11-25']]);
});

it('ramène un horaire à la date locale', function () {
    // 23 h UTC le 20 = 0 h à Paris le 21 ; départ à 11 h le 25.
    expect(parsed(calendarWith("UID:b\r\nDTSTART:20261120T230000Z\r\nDTEND;TZID=Europe/Paris:20261125T110000")))
        ->toBe([['b', '2026-11-21', '2026-11-25']]);
});

it('compte une nuit pour un événement sans fin ou sans durée', function () {
    expect(parsed(calendarWith(
        "UID:c\r\nDTSTART;VALUE=DATE:20261121",
        "UID:d\r\nDTSTART;VALUE=DATE:20261201\r\nDTEND;VALUE=DATE:20261201",
        "UID:e\r\nDTSTART;VALUE=DATE:20261210\r\nDURATION:P3D",
    )))->toBe([['c', '2026-11-21', '2026-11-22'], ['d', '2026-12-01', '2026-12-02'], ['e', '2026-12-10', '2026-12-13']]);
});

it('ignore les événements annulés ou sans identifiant', function () {
    expect(parsed(calendarWith(
        "UID:f\r\nSTATUS:CANCELLED\r\nDTSTART;VALUE=DATE:20261121\r\nDTEND;VALUE=DATE:20261122",
        "DTSTART;VALUE=DATE:20261121\r\nDTEND;VALUE=DATE:20261122",
    )))->toBe([]);
});

it('refuse un fichier qui n’est pas un calendrier', function (string $content) {
    app(IcalParser::class)->parse($content);
})->with(['page HTML' => '<html></html>', 'calendrier tronqué' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:x"])
    ->throws(IcalSyncException::class);
