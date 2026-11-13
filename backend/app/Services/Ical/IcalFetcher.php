<?php

namespace App\Services\Ical;

use App\Exceptions\Ical\IcalSyncException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * Télécharge un flux iCal : requête conditionnelle (If-None-Match), délai court et
 * nouvelles tentatives espacées si la plateforme est momentanément indisponible.
 */
class IcalFetcher
{
    /** Un flux de quelques logements pèse quelques Ko : au-delà, quelque chose cloche. */
    private const MAX_BYTES = 5_000_000;

    /** Pause entre deux tentatives, en millisecondes. */
    private const RETRY_DELAY_MS = 2000;

    public function __construct(private readonly Http $http) {}

    /**
     * @return FetchedCalendar|null null si le flux n'a pas changé (304)
     *
     * @throws IcalSyncException
     */
    public function fetch(string $url, ?string $etag, int $timeoutSeconds, int $attempts): ?FetchedCalendar
    {
        $request = $this->http
            ->withUserAgent(config('app.name').' (synchronisation de calendrier)')
            ->accept('text/calendar, text/plain;q=0.9, */*;q=0.1')
            ->timeout($timeoutSeconds)
            ->retry(max(1, $attempts), self::RETRY_DELAY_MS, fn (?Throwable $e) => $e !== null && $this->isTransient($e), throw: false);
        if ($etag !== null) {
            $request = $request->withHeaders(['If-None-Match' => $etag]);
        }

        try {
            $response = $request->get($url);
        } catch (ConnectionException $e) {
            throw IcalSyncException::unreachable($e);
        }

        if ($response->status() === 304) {
            return null;
        }
        if (! $response->successful()) {
            throw IcalSyncException::httpError($response->status());
        }

        $content = $response->body();
        if (strlen($content) > self::MAX_BYTES) {
            throw IcalSyncException::tooLarge();
        }

        return new FetchedCalendar($content, $response->header('ETag') ?: null);
    }

    /** Coupure réseau ou erreur serveur : la tentative suivante peut réussir. */
    private function isTransient(Throwable $e): bool
    {
        return $e instanceof ConnectionException
            || ($e instanceof RequestException && $e->response->serverError());
    }
}
