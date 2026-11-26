<?php

namespace App\Repositories\Eloquent;

use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentPhotoRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photos des logements, stockées par spatie/laravel-medialibrary.
 */
class ApartmentPhotoRepository implements ApartmentPhotoRepositoryInterface
{
    public function list(Apartment $apartment): Collection
    {
        return $apartment->getMedia(Apartment::PHOTOS);
    }

    public function count(Apartment $apartment): int
    {
        return $apartment->media()->where('collection_name', Apartment::PHOTOS)->count();
    }

    public function findForApartment(Apartment $apartment, int $photoId): Media
    {
        /** @var Media */
        return $apartment->media()
            ->where('collection_name', Apartment::PHOTOS)
            ->whereKey($photoId)
            ->firstOrFail();
    }

    public function add(Apartment $apartment, UploadedFile $file, array $properties): Media
    {
        return $apartment->addMedia($file)
            // Nom de fichier neutre : ni le nom d'origine ni ses caractères ne sont conservés.
            ->usingFileName(Str::uuid()->toString().'.'.$file->guessExtension())
            ->usingName($apartment->slug)
            ->withCustomProperties($properties)
            ->toMediaCollection(Apartment::PHOTOS);
    }

    public function setOrder(array $orderedIds): void
    {
        Media::setNewOrder($orderedIds);
    }

    public function setCustomProperty(Media $photo, string $name, mixed $value): Media
    {
        $photo->setCustomProperty($name, $value)->save();

        return $photo;
    }

    public function delete(Media $photo): void
    {
        $photo->delete();
    }
}
