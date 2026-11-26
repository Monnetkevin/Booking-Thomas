<?php

namespace App\Models;

use App\Enums\ExtraPricing;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Option figée dans une réservation : nom, mode, quantité et prix du jour de la réservation.
 *
 * @property int $booking_id
 * @property int|null $extra_id
 * @property string $name
 * @property ExtraPricing $pricing
 * @property int $quantity
 * @property int $unit_amount
 * @property int $amount
 */
#[Fillable(['booking_id', 'extra_id', 'name', 'pricing', 'quantity', 'unit_amount', 'amount'])]
class BookingExtra extends Model
{
    protected function casts(): array
    {
        return ['pricing' => ExtraPricing::class];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
