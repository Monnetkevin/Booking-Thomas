<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Catalog\PublicCatalogService;
use Illuminate\Http\JsonResponse;

/**
 * Lecture publique des logements publiés (aucune authentification, réponses en cache).
 */
class ApartmentController extends Controller
{
    public function __construct(private readonly PublicCatalogService $catalog) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->catalog->summaries(app()->getLocale())]);
    }

    public function show(string $slug): JsonResponse
    {
        return response()->json(['data' => $this->catalog->detail($slug, app()->getLocale())]);
    }
}
