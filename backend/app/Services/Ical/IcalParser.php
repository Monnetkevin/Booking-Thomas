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
 * ramené à la date locale. Les événements annulés ou sans UID sont ignorés.
 */
class IcalParser
{
    /**
     * @return list<ImportedEvent> un événement par UID
     *
     * @throws IcalSyncException fichier qui n'est pas un calendrier
     */
    public function parse(string $content): array
    {
        if (! str_contains($content, 'BEGIN:VCALENDAR')) {
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

        $events = [];
        foreach ($calendar->select('VEVENT') as $component) {
            if (! $component instanceof VEvent) {
                continue;
            }
            // Accès explicite aux propriétés (celles de sabre/vobject sont magiques).
            $event = $this->event(fn (string $name): mixed => $component->select($name)[0] ?? null);
            if ($event !== null) {
                $events[$event->uid] = $event;
            }
        }

        return array_values($events);
    }

    /**
     * @param  Closure(string): mixed  $property  propriété de l'événement par nom (Property ou null)
     */
    private function event(Closure $property): ?ImportedEvent
    {
        $uid = trim((string) $property('UID'));
        $start = $property('DTSTART');
        if ($uid === '' || ! $start instanceof DateTimeProperty || strtoupper(trim((string) $property('STATUS'))) === 'CANCELLED') {
            return null;
        }

        try {
            $startsOn = $this->localDate($start->getDateTime($this->timezone()));
            $endsOn = $this->endDate($property, $startsOn);
        } catch (Throwable) {
            return null; // date illisible : l'événement est ignoré, pas tout le flux
        }

        return new ImportedEvent(
            uid: mb_substr($uid, 0, 255),
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
