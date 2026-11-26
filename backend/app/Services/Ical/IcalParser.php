<?php

namespace App\Services\Ical;

use App\Exceptions\Ical\IcalSyncException;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property\ICalendar\DateTime as DateTimeProperty;
use Sabre\VObject\Property\ICalendar\Duration;
use Sabre\VObject\Reader;
use Throwable;

/**
 * Lit les événements d'un flux iCal (sabre/vobject). Les plateformes publient des
 * journées entières : DTSTART = arrivée, DTEND = départ (exclu). Un horaire éventuel est
 * ramené à la date locale. Les événements annulés ou sans UID sont ignorés ; ceux dont
 * les dates sont illisibles sont signalés à part, pour que leurs nuits déjà connues ne
 * soient pas prises pour une annulation.
 */
class IcalParser
{
    /**
     * @throws IcalSyncException fichier qui n'est pas un calendrier
     */
    public function parse(string $content): ParsedCalendar
    {
        if (! str_contains($content, 'BEGIN:VCALENDAR')) {
            throw IcalSyncException::invalid();
        }
        // Réponse coupée en route : sabre la lit sans erreur avec les premiers événements, et les
        // séjours manquants passeraient pour des annulations. Le fichier doit se terminer. Seuls les
        // débuts de ligne comptent : une description peut citer « BEGIN:VEVENT ».
        if (! preg_match('/^END:VCALENDAR\s*$/mi', $content)
            || preg_match_all('/^BEGIN:VEVENT\s*$/mi', $content) !== preg_match_all('/^END:VEVENT\s*$/mi', $content)) {
            throw IcalSyncException::invalid();
        }

        try {
            $calendar = Reader::read($content, Reader::OPTION_FORGIVING);
        } catch (ParseException $e) {
            throw IcalSyncException::invalid($e);
        }
        if (! $calendar instanceof VCalendar) {
            throw IcalSyncException::invalid();
        }

        $events = $unreadable = [];
        foreach ($calendar->select('VEVENT') as $component) {
            if (! $component instanceof VEvent) {
                continue;
            }
            // Accès explicite aux propriétés (celles de sabre/vobject sont magiques).
            $property = fn (string $name): mixed => $component->select($name)[0] ?? null;

            $uid = mb_substr(trim((string) $property('UID')), 0, 255);
            if ($uid === '' || strtoupper(trim((string) $property('STATUS'))) === 'CANCELLED') {
                continue;
            }

            $event = $this->event($uid, $property);
            if ($event === null) {
                $unreadable[$uid] = true;
            } else {
                $events[$uid] = $event;
            }
        }

        return new ParsedCalendar(
            array_values($events),
            array_map(strval(...), array_keys(array_diff_key($unreadable, $events))),
        );
    }

    /**
     * @param  Closure(string): mixed  $property  propriété de l'événement par nom (Property ou null)
     * @return ImportedEvent|null null si ses dates sont absentes ou illisibles
     */
    private function event(string $uid, Closure $property): ?ImportedEvent
    {
        $start = $property('DTSTART');
        if (! $start instanceof DateTimeProperty) {
            return null;
        }

        try {
            $startsOn = $this->localDate($start->getDateTime($this->timezone()));
            $endsOn = $this->endDate($property, $startsOn);
        } catch (Throwable) {
            return null;
        }

        return new ImportedEvent(
            uid: $uid,
            startsOn: $startsOn,
            // Une journée au moins : un événement sans durée bloque la nuit de son début.
            endsOn: $endsOn->gt($startsOn) ? $endsOn : $startsOn->addDay(),
            summary: $this->text((string) $property('SUMMARY'), 255),
            url: $this->url([(string) $property('URL'), (string) $property('DESCRIPTION')]),
        );
    }

    /**
     * @param  Closure(string): mixed  $property
     */
    private function endDate(Closure $property, CarbonImmutable $startsOn): CarbonImmutable
    {
        $end = $property('DTEND');
        if ($end instanceof DateTimeProperty) {
            return $this->localDate($end->getDateTime($this->timezone()));
        }
        $duration = $property('DURATION');
        if ($duration instanceof Duration) {
            return $startsOn->add($duration->getDateInterval())->startOfDay();
        }

        return $startsOn->addDay();
    }

    private function localDate(DateTimeInterface $date): CarbonImmutable
    {
        return CarbonImmutable::instance($date)->setTimezone($this->timezone())->startOfDay();
    }

    /**
     * Lien vers la réservation chez la plateforme (propriété URL ou description).
     *
     * @param  list<string>  $texts
     */
    private function url(array $texts): ?string
    {
        foreach ($texts as $text) {
            if (preg_match('#https://[^\s"<>\\\\]+#', $text, $match)) {
                return mb_substr($match[0], 0, 500);
            }
        }

        return null;
    }

    private function text(string $value, int $max): ?string
    {
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function timezone(): DateTimeZone
    {
        return new DateTimeZone((string) config('app.timezone'));
    }
}
