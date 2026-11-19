<?php

namespace App\Http\Controllers;

use App\Services\Ical\IcalExportService;
use Illuminate\Http\Response;

/** GET /ical/{token}.ics : calendrier d'un logement, lu par la plateforme (Airbnb). */
class IcalExportController extends Controller
{
    public function __construct(private readonly IcalExportService $export) {}

    public function __invoke(string $token): Response
    {
        $feed = $this->export->feedForToken($token);
        abort_if($feed === null, 404);

        return response($this->export->calendar($feed))
            ->header('Content-Type', 'text/calendar; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="calendrier.ics"')
            // Toujours à jour : une réservation directe doit être vue au plus tôt.
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex');
    }
}
