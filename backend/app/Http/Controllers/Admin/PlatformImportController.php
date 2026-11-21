<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlatformImport\PreviewPlatformImportRequest;
use App\Http\Requests\Admin\PlatformImport\StorePlatformImportRequest;
use App\Http\Resources\Admin\PlatformImportResource;
use App\Models\PlatformImport;
use App\Services\PlatformImport\PlatformImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Import des revenus Airbnb et Booking.com (fichiers CSV) : historique, aperçu, import. */
class PlatformImportController extends Controller
{
    public function __construct(private readonly PlatformImportService $imports) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', PlatformImport::class);

        return PlatformImportResource::collection($this->imports->history());
    }

    public function preview(PreviewPlatformImportRequest $request): JsonResponse
    {
        Gate::authorize('create', PlatformImport::class);

        return response()->json(['data' => $this->imports->preview($request->platform(), $request->contents())]);
    }

    public function store(StorePlatformImportRequest $request): JsonResponse
    {
        Gate::authorize('create', PlatformImport::class);

        $import = $this->imports->import($request->platform(), $request->filename(), $request->contents(), $request->mapping(), $request->user()?->id);

        return (new PlatformImportResource($import->load('user:id,name')))->response()->setStatusCode(201);
    }
}
