<?php

namespace App\Models;

use App\Enums\PayoutPlatform;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Revenu d'une réservation de plateforme, importé de son fichier CSV (montants en centimes).
 *
 * @property int $id
 * @property PayoutPlatform $platform
 * @property string $confirmation_code
 * @property int $apartment_id
 * @property int|null $platform_import_id
 * @property CarbonImmutable $arrival
 * @property CarbonImmutable $departure
 * @property int $nights
 * @property int|null $gross_amount
 * @property int|null $fees
 * @property int $net_amount
 */
#[Fillable(['platform', 'confirmation_code', 'apartment_id', 'platform_import_id', 'arrival', 'departure', 'nights', 'gross_amount', 'fees', 'net_amount'])]
class PlatformPayout extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => PayoutPlatform::class,
            'arrival' => 'immutable_date',
            'departure' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Apartment, $this> */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class)->withTrashed();
    }
}
