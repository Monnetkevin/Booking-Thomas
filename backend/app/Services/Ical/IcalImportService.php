<?php

namespace App\Services\Ical;

use App\Enums\IcalSyncStatus;
use App\Enums\IcalSyncTrigger;
use App\Events\IcalFeedFailing;
use App\Exceptions\Ical\IcalFeedNotImportableException;
use App\Exceptions\Ical\IcalSyncException;
use App\Models\IcalFeed;
use App\Models\IcalSyncLog;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;

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
     * @throws IcalFeedNotImportableException flux sans URL d'import
     */
    public function sync(IcalFeed $feed, IcalSyncTrigger $trigger = IcalSyncTrigger::Scheduled): IcalSyncLog
    {
        if (blank($feed->import_url)) {
            throw new IcalFeedNotImportableException;
        }

        $startedAt = hrtime(true);
        [$result, $attributes] = $this->read($feed, $trigger);

        return $this->record($feed, $trigger, $result, $attributes, $startedAt);
    }

    /**
     * @return array{0: SyncResult, 1: array<string, mixed>} bilan, et ce qu'il faut retenir du flux lu
     */
    private function read(IcalFeed $feed, IcalSyncTrigger $trigger): array
    {
        try {
            $fetched = $this->fetcher->fetch((string) $feed->import_url, $feed->etag, $trigger->timeoutSeconds(), $trigger->attempts());
            if ($fetched === null) {
                return [SyncResult::unchanged(), []];
            }

            $hash = $fetched->hash();
            if ($hash === $feed->content_hash) {
                return [SyncResult::unchanged(), ['etag' => $fetched->etag]];
            }

            $result = $this->merger->merge($feed, $this->parser->parse($fetched->content));

            return [$result, ['etag' => $fetched->etag, 'content_hash' => $hash]];
        } catch (IcalSyncException $e) {
            return [SyncResult::failed($e->getMessage()), []];
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(IcalFeed $feed, IcalSyncTrigger $trigger, SyncResult $result, array $attributes, int $startedAt): IcalSyncLog
    {
        $failures = $result->failedSync() ? $feed->consecutive_failures + 1 : 0;

        $log = $this->feeds->recordSync($feed, [
            ...$attributes,
            'last_synced_at' => now(),
            'last_status' => $result->status,
            'last_error' => $result->error,
            'consecutive_failures' => $failures,
            ...($result->status === IcalSyncStatus::Failed ? [] : ['last_succeeded_at' => now()]),
        ], [
            'trigger' => $trigger,
            'status' => $result->status,
            'created_count' => $result->created,
            'updated_count' => $result->updated,
            'removed_count' => $result->removed,
            'conflict_count' => $result->conflicts,
            'error' => $result->error,
            'duration_ms' => intdiv(hrtime(true) - $startedAt, 1_000_000),
        ]);

        // Une seule alerte par panne : au 3e échec, pas aux suivants.
        if ($failures === self::FAILURES_BEFORE_ALERT) {
            IcalFeedFailing::dispatch($feed);
        }

        return $log;
    }
}
