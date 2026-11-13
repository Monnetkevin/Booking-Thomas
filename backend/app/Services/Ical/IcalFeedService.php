<?php

namespace App\Services\Ical;

use App\Enums\IcalProvider;
use App\Enums\IcalSyncTrigger;
use App\Exceptions\Ical\IcalFeedAlreadyExistsException;
use App\Exceptions\Ical\IcalFeedNotImportableException;
use App\Jobs\SyncIcalFeed;
use App\Models\Apartment;
use App\Models\IcalFeed;
use App\Models\IcalSyncLog;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Calendriers des plateformes, gérés depuis le back-office : un par logement et par
 * plateforme. Une nouvelle URL d'import est relue tout de suite, depuis zéro.
 */
class IcalFeedService
{
    /** Longueur du token secret de l'URL d'export (spec). */
    public const TOKEN_LENGTH = 40;

    /** Lignes du journal affichées par le back-office. */
    public const LOG_LIMIT = 20;

    public function __construct(
        private readonly IcalFeedRepositoryInterface $feeds,
        private readonly IcalImportService $imports,
    ) {}

    /**
     * @return Collection<int, IcalFeed>
     */
    public function list(): Collection
    {
        return $this->feeds->all();
    }

    /**
     * @throws IcalFeedAlreadyExistsException
     */
    public function create(Apartment $apartment, IcalProvider $provider, ?string $importUrl): IcalFeed
    {
        if ($this->feeds->exists($apartment->id, $provider)) {
            throw new IcalFeedAlreadyExistsException($provider);
        }

        $feed = $this->feeds->create([
            'apartment_id' => $apartment->id,
            'provider' => $provider,
            'import_url' => $importUrl,
            'export_token' => $this->newToken(),
            'is_active' => true,
        ]);
        $this->syncSoon($feed);

        return $feed;
    }

    /**
     * Désactiver un calendrier arrête l'import mais garde ses nuits bloquées : elles ne
     * sont jamais libérées sans que la plateforme l'ait dit.
     *
     * @param  array{import_url?: string|null, is_active?: bool}  $data
     */
    public function update(IcalFeed $feed, array $data): IcalFeed
    {
        if (array_key_exists('import_url', $data) && $data['import_url'] !== $feed->import_url) {
            // Autre calendrier : rien de l'ancien (ETag, contenu, erreurs) ne vaut plus.
            $data += ['etag' => null, 'content_hash' => null, 'last_status' => null, 'last_error' => null, 'consecutive_failures' => 0];
        }

        $this->feeds->update($feed, $data);
        $this->syncSoon($feed);

        return $feed;
    }

    /**
     * @throws IcalFeedNotImportableException
     */
    public function syncNow(IcalFeed $feed): IcalSyncLog
    {
        return $this->imports->sync($feed, IcalSyncTrigger::Manual);
    }

    /**
     * @return Collection<int, IcalSyncLog>
     */
    public function logs(IcalFeed $feed): Collection
    {
        return $this->feeds->recentLogs($feed, self::LOG_LIMIT);
    }

    /**
     * Nouveau token d'export : l'ancienne URL cesse aussitôt de fonctionner (fuite, changement
     * d'annonce). L'URL est à recopier chez la plateforme.
     */
    public function regenerateExportToken(IcalFeed $feed): IcalFeed
    {
        return $this->feeds->update($feed, ['export_token' => $this->newToken()]);
    }

    private function newToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    private function syncSoon(IcalFeed $feed): void
    {
        if ($feed->is_active && filled($feed->import_url) && $feed->last_status === null) {
            SyncIcalFeed::dispatch($feed);
        }
    }
}
