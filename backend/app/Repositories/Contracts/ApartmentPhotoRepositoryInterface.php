<?php

namespace App\Repositories\Contracts;

use App\Models\Apartment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface ApartmentPhotoRepositoryInterface
{
    /**
     * Photos du logement dans l'ordre (la première est la couverture).
     *
     * @return Collection<int, Media>
     */
    public function list(Apartment $apartment): Collection;

    public function count(Apartment $apartment): int;

    /** Photo de ce logement, ou ModelNotFoundException (404). */
    public function findForApartment(Apartment $apartment, int $photoId): Media;

    /**
     * @param  array<string, mixed>  $properties
     */
    public function add(Apartment $apartment, UploadedFile $file, array $properties): Media;

    /**
     * @param  list<int>  $orderedIds
     */
    public function setOrder(array $orderedIds): void;

    public function setCustomProperty(Media $photo, string $name, mixed $value): Media;

    public function delete(Media $photo): void;
}
