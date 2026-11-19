<?php

namespace App\Repositories\Contracts;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AuditLog;

    /**
     * Journal, le plus récent d'abord, avec l'auteur.
     *
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function paginate(?AuditAction $action, int $perPage): LengthAwarePaginator;
}
