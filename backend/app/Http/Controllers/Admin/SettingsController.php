<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Réglages de l'hôte : délais, taxe de séjour, barèmes d'annulation, e-mails. */
class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function show(): JsonResponse
    {
        Gate::authorize('view', Setting::class);

        return response()->json(['data' => $this->settings->current()]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        Gate::authorize('update', Setting::class);

        return response()->json(['data' => $this->settings->update($request->settings())]);
    }
}
