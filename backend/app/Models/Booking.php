<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use Carbon\CarbonImmutable;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property BookingStatus $status
 * @property BookingSource $source
 * @property CarbonImmutable $arrival
 * @property CarbonImmutable $departure
 * @property Carbon|null $expires_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property array<string, mixed> $price_breakdown
 * @property array{policy: string, tiers: list<array{days_before: int, refund_percent: int}>} $cancellation_policy
 */
#[Fillable([
    'reference', 'apartment_id', 'source', 'status', 'guest_name', 'guest_email', 'guest_phone',
    'guest_country', 'guest_message', 'locale', 'adults', 'children', 'housekeeping_notes',
    'arrival', 'departure', 'accommodation_amount', 'cleaning_fee', 'extras_amount', 'tourist_tax',
    'discount_amount', 'total_amount', 'price_breakdown', 'cancellation_policy',
    'stripe_customer_id', 'stripe_checkout_session_id', 'expires_at', 'confirmed_at', 'cancelled_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => BookingSource::class,
            'status' => BookingStatus::class,
            'arrival' => 'immutable_date',
            'departure' => 'immutable_date',
            'price_breakdown' => 'array',
            'cancellation_policy' => 'array',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function nights(): int
    {
        return (int) $this->arrival->diffInDays($this->departure);
    }

    /** @return BelongsTo<Apartment, $this> */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /** @return HasOne<AvailabilityBlock, $this> */
    public function availabilityBlock(): HasOne
    {
        return $this->hasOne(AvailabilityBlock::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
