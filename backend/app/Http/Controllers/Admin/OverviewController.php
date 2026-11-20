<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Dashboard\OverviewService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Compteurs de la barre du back-office (menu, bouton « Alertes »). */
class OverviewController extends Controller
{
    public function __construct(private readonly OverviewService $overview) {}

    public function __invoke(): JsonResponse
    {
        Gate::authorize('viewAny', Booking::class);

        return response()->json(['data' => $this->overview->counts(CarbonImmutable::today())]);
    }
}
