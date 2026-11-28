<?php

namespace App\Services\Ical;

use App\Enums\IcalSyncTrigger;
use App\Events\IcalEmptyCalendarRefused;
use App\Events\IcalFeedFailing;
use App\Events\IcalFeedRecovered;
use App\Exceptions\Ical\EmptyCalendarRefusedException;
use App\Exceptions\Ical\IcalFeedNotImportableException;
use App\Exceptions\Ical\IcalSyncException;
use App\Models\IcalFeed;
use App\Models\IcalSyncLog;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use Throwable;

/**
 * Synchronisation d'un flux (spec « Algorithme d'import ») : téléchargement conditionnel,
 * contenu identique ignoré, lecture des événements, application aux blocs. Toute erreur
 * est enregistrée sur le flux et ne libère aucune nuit ; l'hôte est alerté au 3e échec
 * consécutif. Chaque lecture laisse une ligne dans le journal du flux.
 */
class IcalImportService
{
    /** Échecs consécutifs avant d'alerter l'hôte (spec). */
    public const FAILURES_BEFORE_ALERT = 3;

    public function __construct(
        private readonly IcalFetcher $fetcher,
        private readonly IcalParser $parser,
        private readonly IcalCalendarMerger $merger,
        private readonly IcalFeedRepositoryInterface $feeds,
    ) {}

    /**
     * @param  bool  $acceptEmpty  l'hôte confirme qu'un calendrier vide est normal (dernier
     *                             séjour annulé chez la plateforme) : ses nuits sont libérées
     *
     * @throws IcalFeedNotImportableException flux sans URL d'import
     */
    public function sync(IcalFeed $feed, IcalSyncTrigger $trigger = IcalSyncTrigger::Scheduled, bool $acceptEmpty = false): IcalSyncLog
    {
        if (blank($feed->import_url)) {
            throw new IcalFeedNotImportableException;
        }

        $startedAt = hrtime(true);
        [$result, $attributes] = $this->read($feed, $trigger, $acceptEmpty);

        return $this->record($feed, $trigger, $result, $attributes, $startedAt);
    }

    /**
     * @return array{0: SyncResult, 1: array<string, mixed>} bilan, et ce qu'il faut retenir du flux lu
     */
    private function read(IcalFeed $feed, IcalSyncTrigger $trigger, bool $acceptEmpty): array
    {
        try {
            $fetched = $this->fetcher->fetch((string) $feed->import_url, $feed->provider, $feed->etag, $trigger->timeoutSeconds(), $trigger->attempts());
            if ($fetched === null) {
                return [SyncResult::unchanged(), []];
            }

            $hash = $fetched->hash();
            if ($hash === $feed->content_hash) {
                return [SyncResult::unchanged(), ['etag' => $fetched->etag]];
            }

            $calendar = $this->parser->parse($fetched->content);
            $result = $this->merger->merge($feed, $calendar->events, $calendar->unreadableUids, $acceptEmpty);

            return [$result, ['etag' => $fetched->etag, 'content_hash' => $hash]];
        } catch (IcalSyncException $e) {
            return [SyncResult::failed($e->getMessage(), $e instanceof EmptyCalendarRefusedException), []];
        } catch (Throwable $e) {
            // Erreur imprévue (base, contenu inattendu) : un échec comme un autre, journalisé et
            // compté, pour que l'hôte soit alerté au lieu d'un calendrier qui cesse d'être lu.
            report($e);

            return [SyncResult::failed(__('ical.errors.unexpected', [], 'fr')), []];
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(IcalFeed $feed, IcalSyncTrigger $trigger, SyncResult $result, array $attributes, int $startedAt): IcalSyncLog
    {
        $failures = $result->failedSync() ? $feed->consecutive_failures + 1 : 0;
        $emptyRefusal = $result->emptyRefusal();
        // État d'avant cette lecture : les alertes ne partent qu'au changement d'état.
        $wasAlerted = $feed->consecutive_failures >= self::FAILURES_BEFORE_ALERT;
        $wasRefused = $feed->empty_calendar_refused;

        $log = $this->feeds->recordSync($feed, [
            ...$attributes,
            'last_synced_at' => now(),
            'last_status' => $result->status,
            'last_error' => $result->error,
            'consecutive_failures' => $failures,
            ...($result->failedSync() ? [] : ['last_succeeded_at' => now()]),
            ...($emptyRefusal === null ? [] : ['empty_calendar_refused' => $emptyRefusal]),
        ], [
            'trigger' => $trigger,
            'status' => $result->status,
            'created_count' => $result->created,
            'updated_count' => $result->updated,
            'removed_count' => $result->removed,
            'conflict_count' => $result->conflicts,
            'unreadable_count' => $result->unreadable,
            'error' => $result->error,
            'duration_ms' => intdiv(hrtime(true) - $startedAt, 1_000_000),
        ]);

        // Une seule alerte par panne : au 3e échec, pas aux suivants ; puis au retour à la normale.
        if ($failures === self::FAILURES_BEFORE_ALERT) {
            IcalFeedFailing::dispatch($feed);
        } elseif ($wasAlerted && ! $result->failedSync()) {
            IcalFeedRecovered::dispatch($feed);
        }
        if ($emptyRefusal === true && ! $wasRefused) {
            IcalEmptyCalendarRefused::dispatch($feed);
        }

        return $log;
    }
}
