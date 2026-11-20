<?php

namespace App\Models;

use App\Enums\PayoutPlatform;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un import de fichier de revenus d'une plateforme.
 *
 * @property int $id
 * @property PayoutPlatform $platform
 * @property string $filename
 * @property int|null $user_id
 * @property int $rows_created
 * @property int $rows_updated
 * @property int $rows_skipped
 * @property Carbon $created_at
 */
#[Fillable(['platform', 'filename', 'user_id', 'rows_created', 'rows_updated', 'rows_skipped'])]
class PlatformImport extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['platform' => PayoutPlatform::class];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
