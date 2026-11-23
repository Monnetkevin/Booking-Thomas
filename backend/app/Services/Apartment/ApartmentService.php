<?php

namespace App\Services\Apartment;

use App\Enums\ApartmentStatus;
use App\Enums\AuditAction;
use App\Exceptions\Apartment\ApartmentHasUpcomingStaysException;
use App\Exceptions\Apartment\ApartmentNotPublishableException;
use App\Jobs\GeocodeApartment;
use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\CatalogCache;
use App\Services\Geocoding\Geocoder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * Création, modification, publication et suppression des logements.
 */
class ApartmentService
{
    /** Champs de l'adresse postale : en changer recalcule la position sur la carte. */
    private const ADDRESS_FIELDS = ['address', 'postal_code', 'city'];

    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly Geocoder $geocoder,
        private readonly CatalogCache $catalog,
        private readonly AuditLogger $audit,
    ) {}

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
     * @param  bool  $canManagePricing  faux sans le droit de fixer les prix : prix ignorés
     */
    public function create(array $data, bool $canManagePricing = true): Apartment
    {
        $data = $this->withoutPricesUnlessAllowed($data, $canManagePricing);
        // Valeur par défaut de la base, posée ici pour que la réponse la renvoie (et non null).
        $data['cleaning_fee'] ??= 0;
        $amenityIds = $data['amenity_ids'] ?? null;
        unset($data['amenity_ids']);

        $data['slug'] = $this->uniqueSlug($data['name']['fr']);
        $data['status'] = ApartmentStatus::Draft;
        $data['position'] = $this->apartments->nextPosition();

        $apartment = $this->apartments->create($data);
        // Frais de ménage fixés dès la création : journalisés comme leurs modifications.
        if ((int) $data['cleaning_fee'] !== 0) {
            $this->audit->record(AuditAction::ApartmentPriceUpdated, $apartment, before: ['cleaning_fee' => 0], after: ['cleaning_fee' => (int) $data['cleaning_fee']]);
        }

        if (is_array($amenityIds)) {
            $this->apartments->syncAmenities($apartment, $amenityIds);
        }

        GeocodeApartment::dispatch($apartment);

        $this->catalog->flush();

        return $this->details($apartment);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  bool  $canManagePricing  faux sans le droit de fixer les prix : prix ignorés
     */
    public function update(Apartment $apartment, array $data, bool $canManagePricing = true): Apartment
    {
        $data = $this->withoutPricesUnlessAllowed($data, $canManagePricing);
        $amenityIds = $data['amenity_ids'] ?? null;
        unset($data['amenity_ids'], $data['status'], $data['position'], $data['slug']);

        // L'adresse de la page suit toujours le nom français.
        $newName = $data['name']['fr'] ?? null;
        if (is_string($newName) && $newName !== $apartment->getTranslation('name', 'fr', false)) {
            $data['slug'] = $this->uniqueSlug($newName, $apartment->id);
        }

        // Nouvelle adresse : l'ancienne position est effacée jusqu'au nouveau calcul.
        $addressChanged = collect(self::ADDRESS_FIELDS)
            ->contains(fn (string $field) => array_key_exists($field, $data) && $data[$field] !== $apartment->{$field});
        if ($addressChanged) {
            $data += ['latitude' => null, 'longitude' => null, 'geocoded_at' => null];
        }

        $previousFee = $apartment->cleaning_fee;
        $this->apartments->update($apartment, $data);
        // Frais de ménage : un prix, journalisé comme les tarifs.
        if (array_key_exists('cleaning_fee', $data) && (int) $data['cleaning_fee'] !== $previousFee) {
            $this->audit->record(AuditAction::ApartmentPriceUpdated, $apartment, before: ['cleaning_fee' => $previousFee], after: ['cleaning_fee' => (int) $data['cleaning_fee']]);
        }

        if (is_array($amenityIds)) {
            $this->apartments->syncAmenities($apartment, $amenityIds);
        }

        if ($addressChanged) {
            GeocodeApartment::dispatch($apartment);
        }

        $this->catalog->flush();

        return $this->details($apartment);
    }

    /**
     * Programme le calcul de position des logements qui n'en ont jamais eu.
     *
     * @return int nombre de logements concernés
     */
    public function geocodeMissing(): int
    {
        $apartments = $this->apartments->awaitingGeocoding();
        $apartments->each(fn (Apartment $apartment) => GeocodeApartment::dispatch($apartment));

        return $apartments->count();
    }

    /**
     * Position sur la carte d'après l'adresse postale (appelé par la tâche GeocodeApartment).
     * Une adresse introuvable laisse les coordonnées vides ; un service injoignable lève une
     * exception pour que la tâche soit réessayée.
     */
    public function refreshCoordinates(Apartment $apartment): void
    {
        $coordinates = $this->geocoder->geocode($apartment->address, $apartment->postal_code, $apartment->city);

        $this->apartments->update($apartment, [
            'latitude' => $coordinates?->latitude,
            'longitude' => $coordinates?->longitude,
            'geocoded_at' => now(),
        ]);
        $this->catalog->flush();
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

        $wasPublished = $apartment->status === ApartmentStatus::Published;
        $this->apartments->update($apartment, ['status' => ApartmentStatus::Published]);
        if (! $wasPublished) {
            $this->audit->record(AuditAction::ApartmentPublished, $apartment, after: ['name' => $apartment->getTranslation('name', 'fr')]);
        }

        $this->catalog->flush();

        return $this->details($apartment);
    }

    /** Retire le logement du site ; les réservations existantes ne bougent pas. */
    public function unpublish(Apartment $apartment): Apartment
    {
        $wasPublished = $apartment->status === ApartmentStatus::Published;
        $this->apartments->update($apartment, ['status' => ApartmentStatus::Draft]);
        if ($wasPublished) {
            $this->audit->record(AuditAction::ApartmentUnpublished, $apartment, before: ['name' => $apartment->getTranslation('name', 'fr')]);
        }

        $this->catalog->flush();

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
        $this->audit->record(AuditAction::ApartmentDeleted, $apartment, before: ['name' => $apartment->getTranslation('name', 'fr'), 'slug' => $apartment->slug]);
        $this->catalog->flush();
    }

    /**
     * @param  list<int>  $amenityIds
     */
    public function syncAmenities(Apartment $apartment, array $amenityIds): Apartment
    {
        $this->apartments->syncAmenities($apartment, $amenityIds);

        $this->catalog->flush();

        return $this->details($apartment);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->apartments->reorder($orderedIds);
        $this->catalog->flush();
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

    /**
     * Les prix (frais de ménage) ne sont fixés que par qui en a le droit (ApartmentPolicy::
     * managePricing) : sinon ils sont ignorés, la fiche s'enregistre sans eux.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withoutPricesUnlessAllowed(array $data, bool $canManagePricing): array
    {
        if (! $canManagePricing) {
            unset($data['cleaning_fee']);
        }

        return $data;
    }
}
