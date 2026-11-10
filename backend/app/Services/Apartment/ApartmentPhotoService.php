<?php

namespace App\Services\Apartment;

use App\Exceptions\Photo\InvalidPhotoOrderException;
use App\Exceptions\Photo\TooManyPhotosException;
use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentPhotoRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photos d'un logement : envoi, ordre (la première est la couverture), texte alternatif.
 * Les versions WebP et AVIF sont générées en tâche de fond par Medialibrary.
 */
class ApartmentPhotoService
{
    public const MAX_PHOTOS = 30;

    public function __construct(private readonly ApartmentPhotoRepositoryInterface $photos) {}

    /**
     * @return Collection<int, Media>
     */
    public function list(Apartment $apartment): Collection
    {
        return $this->photos->list($apartment);
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, Media>
     *
     * @throws TooManyPhotosException
     */
    public function upload(Apartment $apartment, array $files): Collection
    {
        if ($this->photos->count($apartment) + count($files) > self::MAX_PHOTOS) {
            throw new TooManyPhotosException(self::MAX_PHOTOS);
        }

        return collect($files)->map(function (UploadedFile $file) use ($apartment): Media {
            [$width, $height] = getimagesize($file->getRealPath()) ?: [null, null];

            return $this->photos->add($apartment, $file, [
                'alt' => ['fr' => '', 'en' => ''],
                'width' => $width,
                'height' => $height,
            ]);
        });
    }

    /**
     * @param  list<int>  $orderedIds  toutes les photos du logement, dans le nouvel ordre
     * @return Collection<int, Media>
     *
     * @throws InvalidPhotoOrderException
     */
    public function reorder(Apartment $apartment, array $orderedIds): Collection
    {
        $current = $this->photos->list($apartment)->pluck('id')->sort()->values()->all();
        $requested = collect($orderedIds)->sort()->values()->all();

        if ($current !== $requested) {
            throw new InvalidPhotoOrderException;
        }

        DB::transaction(fn () => $this->photos->setOrder($orderedIds));

        return $this->photos->list($apartment->refresh());
    }

    /**
     * @param  array{fr?: string|null, en?: string|null}  $alt
     */
    public function updateAlt(Apartment $apartment, int $photoId, array $alt): Media
    {
        $photo = $this->photos->findForApartment($apartment, $photoId);

        return $this->photos->setCustomProperty($photo, 'alt', [
            'fr' => trim((string) ($alt['fr'] ?? '')),
            'en' => trim((string) ($alt['en'] ?? '')),
        ]);
    }

    public function delete(Apartment $apartment, int $photoId): void
    {
        $this->photos->delete($this->photos->findForApartment($apartment, $photoId));
    }
}
