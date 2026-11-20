<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
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
     * Actions journalisées sur un objet (ex. « Booking », 42), avec leur auteur, dans l'ordre.
     *
     * @return Collection<int, AuditLog>
     */
    public function forSubject(string $type, string $id): Collection;

    /**
     * Journal, le plus récent d'abord, avec l'auteur.
     *
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function paginate(?AuditAction $action, int $perPage): LengthAwarePaginator;
}
