<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
