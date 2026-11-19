<?php

namespace App\Repositories\Eloquent;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $attributes): AuditLog
    {
        return AuditLog::query()->create($attributes);
    }

    public function paginate(?AuditAction $action, int $perPage): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->when($action !== null, fn (Builder $query) => $query->where('action', $action?->value))
            ->latest('id')
            ->paginate($perPage);
    }
}
