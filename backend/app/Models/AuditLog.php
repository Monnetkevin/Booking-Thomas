<?php

namespace App\Models;

use App\Enums\AuditAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une action sensible du back-office. Jamais modifiée ; supprimée au bout d'un an
 * (l'adresse IP est une donnée personnelle).
 *
 * @property int $id
 * @property int|null $user_id
 * @property AuditAction $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property string|null $ip
 * @property Carbon $created_at
 */
#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'before', 'after', 'ip'])]
class AuditLog extends Model
{
    use MassPrunable;

    public const KEEP_MONTHS = 12;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
