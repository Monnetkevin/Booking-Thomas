<?php

namespace App\Models;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use Carbon\CarbonImmutable;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Code promo ou offre automatique, paramétré par l'hôte.
 *
 * @property int $id
 * @property string $code
 * @property CouponTarget $target
 * @property CouponType $type
 * @property int $value
 * @property int|null $extra_id
 * @property int $free_quantity
 * @property CarbonImmutable|null $starts_on
 * @property CarbonImmutable|null $ends_on
 * @property int|null $max_uses
 * @property int|null $max_uses_per_email
 * @property int|null $min_nights
 * @property int|null $min_amount
 * @property bool $automatic
 * @property bool $is_active
 * @property-read Extra|null $extra
 */
#[Fillable([
    'code', 'target', 'type', 'value', 'extra_id', 'free_quantity', 'starts_on', 'ends_on', 'max_uses',
    'max_uses_per_email', 'min_nights', 'min_amount', 'automatic', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target' => CouponTarget::class,
            'type' => CouponType::class,
            'value' => 'integer',
            'free_quantity' => 'integer',
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'max_uses' => 'integer',
            'max_uses_per_email' => 'integer',
            'min_nights' => 'integer',
            'min_amount' => 'integer',
            'automatic' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Extra, $this> */
    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    /**
     * Logements concernés ; aucun = tous.
     *
     * @return BelongsToMany<Apartment, $this>
     */
    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class);
    }

    /** @return HasMany<CouponRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }
}
