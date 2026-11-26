<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avis d'un voyageur sur un séjour réservé sur le site. Un seul par réservation.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $apartment_id
 * @property ReviewStatus $status
 * @property string $token_hash
 * @property CarbonImmutable $invited_at
 * @property CarbonImmutable $expires_at
 * @property int|null $rating
 * @property string|null $comment
 * @property string|null $display_name
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $published_at
 * @property string|null $rejection_reason
 * @property string|null $host_reply
 * @property-read Booking $booking
 * @property-read Apartment $apartment
 */
#[Fillable([
    'booking_id', 'apartment_id', 'status', 'token_hash', 'invited_at', 'expires_at', 'rating', 'comment',
    'display_name', 'submitted_at', 'published_at', 'rejection_reason', 'host_reply',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'invited_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'rating' => 'integer',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<Apartment, $this> */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class)->withTrashed();
    }
}
