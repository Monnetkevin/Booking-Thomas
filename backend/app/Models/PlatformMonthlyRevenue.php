<?php

namespace App\Models;

use App\Enums\IcalProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Net versé par une plateforme pour un logement et un mois, saisi à la main par l'admin
 * (rapport mensuel de la plateforme). Une ligne absente : montant pas encore saisi.
 *
 * @property int $id
 * @property IcalProvider $platform
 * @property int $apartment_id
 * @property CarbonImmutable $month premier jour du mois
 * @property int $net_amount centimes
 * @property int|null $user_id
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['platform', 'apartment_id', 'month', 'net_amount', 'user_id'])]
class PlatformMonthlyRevenue extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => IcalProvider::class,
            'month' => 'immutable_date',
            'net_amount' => 'integer',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
