<?php

namespace App\Repositories\Eloquent;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function forSubject(string $type, string $id): Collection
    {
        return AuditLog::query()->with('user:id,name')->where('subject_type', $type)->where('subject_id', $id)->orderBy('created_at')->orderBy('id')->get();
    }

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
