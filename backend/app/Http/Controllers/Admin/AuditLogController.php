<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Audit\ListAuditLogsRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Lecture du journal des actions sensibles. */
class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(ListAuditLogsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AuditLog::class);

        return AuditLogResource::collection($this->audit->list($request->action()));
    }
}
