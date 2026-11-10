<?php

namespace App\Models;

use App\Enums\ApartmentStatus;
use App\Enums\CancellationPolicy;
use Database\Factories\ApartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * @property ApartmentStatus $status
 * @property CancellationPolicy $cancellation_policy
 */
#[Fillable([
    'slug', 'name', 'description', 'address', 'postal_code', 'city', 'latitude', 'longitude',
    'capacity', 'bedrooms', 'beds', 'bathrooms', 'surface_m2', 'check_in_time', 'check_out_time',
    'min_stay', 'max_stay', 'turnover_days', 'cleaning_fee', 'cancellation_policy',
    'registration_number', 'status', 'position',
])]
class Apartment extends Model implements HasMedia
{
    /** @use HasFactory<ApartmentFactory> */
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** Collection Medialibrary des photos ; la première est la couverture. */
    public const PHOTOS = 'photos';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'status' => ApartmentStatus::class,
            'cancellation_policy' => CancellationPolicy::class,
        ];
    }

    /** Tailles générées pour chaque photo : [largeur, hauteur]. */
    public const PHOTO_SIZES = [
        'thumb' => [480, 360],
        'card' => [960, 720],
        'large' => [1920, 1280],
    ];

    /**
     * Les originaux restent privés (disque local) ; seules les versions ré-encodées,
     * sans métadonnées, sont publiques.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTOS)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('local')
            ->storeConversionsOnDisk('public');
    }

    /** WebP pour tous les navigateurs, AVIF plus léger pour ceux qui le lisent (srcset). */
    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (self::PHOTO_SIZES as $name => [$width, $height]) {
            $fit = $name === 'thumb' ? Fit::Crop : Fit::Max;

            $this->addMediaConversion($name)
                ->performOnCollections(self::PHOTOS)
                ->fit($fit, $width, $height)
                ->format('webp')
                ->quality(80);

            $this->addMediaConversion("$name-avif")
                ->performOnCollections(self::PHOTOS)
                ->fit($fit, $width, $height)
                ->format('avif')
                ->quality(60);
        }
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ApartmentStatus::Published);
    }

    /** @return BelongsToMany<Amenity, $this> */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    /** @return HasMany<RatePeriod, $this> */
    public function ratePeriods(): HasMany
    {
        return $this->hasMany(RatePeriod::class)->orderBy('starts_on');
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<AvailabilityBlock, $this> */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    /** @return HasMany<IcalFeed, $this> */
    public function icalFeeds(): HasMany
    {
        return $this->hasMany(IcalFeed::class);
    }
}
