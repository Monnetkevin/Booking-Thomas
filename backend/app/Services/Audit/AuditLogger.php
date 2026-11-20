<?php

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal d'audit des actions sensibles du back-office (annulations, suppressions, tarifs,
 * réglages, comptes) : auteur, action, objet, valeurs avant et après, adresse IP.
 * Les valeurs sont celles que l'appelant choisit : jamais de mot de passe ni de jeton.
 */
class AuditLogger
{
    public const PER_PAGE = 50;

    public function __construct(
        private readonly AuditLogRepositoryInterface $logs,
        private readonly AuditActor $actor,
    ) {}

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(AuditAction $action, ?Model $subject = null, ?array $before = null, ?array $after = null): AuditLog
    {
        return $this->logs->create([
            'user_id' => $this->actor->user()?->id,
            'action' => $action,
            'subject_type' => $subject !== null ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey() !== null ? (string) $subject->getKey() : null,
            'before' => $before,
            'after' => $after,
            'ip' => $this->actor->ip(),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function list(?AuditAction $action = null): LengthAwarePaginator
    {
        return $this->logs->paginate($action, self::PER_PAGE);
    }
}
