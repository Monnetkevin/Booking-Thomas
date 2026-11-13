<?php

namespace App\Services\Ical;

use App\Exceptions\Ical\IcalFeedNotImportableException;
use App\Exceptions\Ical\IcalSyncException;
use App\Models\IcalFeed;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;

/**
 * Synchronisation d'un flux (spec « Algorithme d'import ») : téléchargement conditionnel,
 * contenu identique ignoré, lecture des événements, application aux blocs. Toute erreur
 * est enregistrée sur le flux et ne libère aucune nuit.
 */
class IcalImportService
{
    /** Synchronisation planifiée : 10 secondes au plus par tentative, 3 tentatives. */
    public const TIMEOUT_SECONDS = 10;

    public const ATTEMPTS = 3;

    public function __construct(
        private readonly IcalFetcher $fetcher,
        private readonly IcalParser $parser,
        private readonly IcalCalendarMerger $merger,
        private readonly IcalFeedRepositoryInterface $feeds,
    ) {}

    /**
     * @throws IcalFeedNotImportableException flux sans URL d'import
     */
    public function sync(IcalFeed $feed, int $attempts = self::ATTEMPTS, int $timeoutSeconds = self::TIMEOUT_SECONDS): SyncResult
    {
        if (blank($feed->import_url)) {
            throw new IcalFeedNotImportableException;
        }

        try {
            $fetched = $this->fetcher->fetch((string) $feed->import_url, $feed->etag, $timeoutSeconds, $attempts);
            if ($fetched === null) {
                return $this->record($feed, SyncResult::unchanged());
            }

            $hash = $fetched->hash();
            if ($hash === $feed->content_hash) {
                return $this->record($feed, SyncResult::unchanged(), ['etag' => $fetched->etag]);
            }

            $result = $this->merger->merge($feed, $this->parser->parse($fetched->content));

            return $this->record($feed, $result, ['etag' => $fetched->etag, 'content_hash' => $hash]);
        } catch (IcalSyncException $e) {
            return $this->record($feed, SyncResult::failed($e->getMessage()));
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(IcalFeed $feed, SyncResult $result, array $attributes = []): SyncResult
    {
        $this->feeds->update($feed, [
            ...$attributes,
            'last_synced_at' => now(),
            'last_status' => $result->status,
            'last_error' => $result->error,
            'consecutive_failures' => $result->failedSync() ? $feed->consecutive_failures + 1 : 0,
        ]);

        return $result;
    }
}
