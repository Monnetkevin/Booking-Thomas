<?php

namespace App\Services\Apartment;

use App\Enums\ApartmentStatus;
use App\Exceptions\Apartment\ApartmentHasUpcomingStaysException;
use App\Exceptions\Apartment\ApartmentNotPublishableException;
use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * Création, modification, publication et suppression des logements.
 */
class ApartmentService
{
    public function __construct(private readonly ApartmentRepositoryInterface $apartments) {}

    /**
     * @return Collection<int, Apartment>
     */
    public function list(?ApartmentStatus $status = null): Collection
    {
        return $this->apartments->list($status);
    }

    public function details(Apartment $apartment): Apartment
    {
        return $this->apartments->loadDetails($apartment);
    }

    /**
     * Un logement naît toujours en brouillon : il n'est publié qu'une fois complet.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Apartment
    {
        $amenityIds = $data['amenity_ids'] ?? null;
        unset($data['amenity_ids']);

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']['fr']);
        $data['status'] = ApartmentStatus::Draft;
        $data['position'] = $this->apartments->nextPosition();

        $apartment = $this->apartments->create($data);

        if (is_array($amenityIds)) {
            $this->apartments->syncAmenities($apartment, $amenityIds);
        }

        return $this->details($apartment);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Apartment $apartment, array $data): Apartment
    {
        $amenityIds = $data['amenity_ids'] ?? null;
        unset($data['amenity_ids'], $data['status'], $data['position']);

        if (array_key_exists('slug', $data)) {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?: $apartment->getTranslation('name', 'fr'), $apartment->id);
        }

        $this->apartments->update($apartment, $data);

        if (is_array($amenityIds)) {
            $this->apartments->syncAmenities($apartment, $amenityIds);
        }

        return $this->details($apartment);
    }

    /**
     * Publie le logement s'il a ce qu'il faut pour être réservé.
     *
     * @throws ApartmentNotPublishableException
     */
    public function publish(Apartment $apartment): Apartment
    {
        $missing = $this->missingForPublication($apartment);
        if ($missing !== []) {
            throw new ApartmentNotPublishableException($missing);
        }

        $this->apartments->update($apartment, ['status' => ApartmentStatus::Published]);

        return $this->details($apartment);
    }

    /** Retire le logement du site ; les réservations existantes ne bougent pas. */
    public function unpublish(Apartment $apartment): Apartment
    {
        $this->apartments->update($apartment, ['status' => ApartmentStatus::Draft]);

        return $this->details($apartment);
    }

    /**
     * Suppression logique : l'historique (réservations passées, paiements) est conservé.
     *
     * @throws ApartmentHasUpcomingStaysException
     */
    public function delete(Apartment $apartment): void
    {
        if ($this->apartments->hasUpcomingStays($apartment, Date::today())) {
            throw new ApartmentHasUpcomingStaysException;
        }

        $this->apartments->delete($apartment);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->apartments->reorder($orderedIds);
    }

    /**
     * @return list<string>
     */
    public function missingForPublication(Apartment $apartment): array
    {
        $missing = [];

        if (blank($apartment->getTranslation('description', 'fr', false))) {
            $missing[] = 'description';
        }
        if ($this->apartments->photoCount($apartment) === 0) {
            $missing[] = 'photos';
        }
        if (! $this->apartments->hasUpcomingRatePeriod($apartment, Date::today())) {
            $missing[] = 'rates';
        }

        return $missing;
    }

    /** « Le Cocon » → « le-cocon », puis « le-cocon-2 » si déjà pris. */
    private function uniqueSlug(string $source, ?int $exceptId = null): string
    {
        $base = Str::slug($source) ?: 'logement';
        $slug = $base;

        for ($suffix = 2; $this->apartments->slugExists($slug, $exceptId); $suffix++) {
            $slug = "$base-$suffix";
        }

        return $slug;
    }
}
