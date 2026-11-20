<?php

namespace App\Models;

use App\Enums\PayoutPlatform;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Annonce d'une plateforme rattachée à un logement (nom tel qu'il figure dans l'export).
 *
 * @property int $id
 * @property PayoutPlatform $platform
 * @property string $listing
 * @property int $apartment_id
 */
#[Fillable(['platform', 'listing', 'apartment_id'])]
class PlatformListing extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['platform' => PayoutPlatform::class];
    }
}
