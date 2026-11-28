<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Message envoyé depuis le formulaire de contact, lu dans le back-office.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $message
 * @property int|null $apartment_id
 * @property string $locale
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Apartment|null $apartment
 */
#[Fillable(['name', 'email', 'phone', 'message', 'apartment_id', 'locale', 'read_at'])]
class ContactMessage extends Model
{
    use MassPrunable;

    /** Messages supprimés un an après leur envoi (RGPD, décision du 2026-11-28). */
    public const KEEP_MONTHS = 12;

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /** @return BelongsTo<Apartment, $this> */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class)->withTrashed();
    }
}
