<?php

namespace App\Services\Extra;

use App\Exceptions\Extra\ExtraNotAvailableException;
use App\Models\Apartment;
use App\Models\Extra;
use App\Repositories\Contracts\ExtraRepositoryInterface;
use App\Services\Booking\Stay;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

/**
 * Options qu'un séjour peut recevoir (spec « Options par logement ») : actives, proposées
 * dans le logement et, pour le voyageur, commandées avant le délai (par exemple 48 h avant
 * l'heure d'arrivée). L'hôte qui saisit une réservation n'est pas tenu par ce délai.
 */
class ExtraSelector
{
    public function __construct(private readonly ExtraRepositoryInterface $extras) {}

    /**
     * Options encore commandables pour ce séjour.
     *
     * @return Collection<int, Extra>
     */
    public function available(Apartment $apartment, Stay $stay): Collection
    {
        return $this->extras->activeForApartment($apartment->id)
            ->filter(fn (Extra $extra) => $this->inTime($extra, $apartment, $stay))
            ->values();
    }

    /**
     * @param  list<ExtraChoice>  $choices
     * @return list<SelectedExtra>
     *
     * @throws ExtraNotAvailableException
     */
    public function select(Apartment $apartment, Stay $stay, array $choices, bool $enforceLeadTime = true): array
    {
        if ($choices === []) {
            return [];
        }
        $offered = $this->extras->activeForApartment($apartment->id)->keyBy('id');

        return array_map(function (ExtraChoice $choice) use ($offered, $apartment, $stay, $enforceLeadTime): SelectedExtra {
            $extra = $offered->get($choice->extraId);
            $quantity = $extra?->pricing->hasQuantity() ? $choice->quantity : 1;
            if ($extra === null || $quantity < 1 || $quantity > $extra->max_quantity || ($enforceLeadTime && ! $this->inTime($extra, $apartment, $stay))) {
                throw new ExtraNotAvailableException;
            }

            return new SelectedExtra($extra, $quantity);
        }, $choices);
    }

    /** Commandée assez tôt : au plus tard « délai » heures avant l'heure d'arrivée. */
    private function inTime(Extra $extra, Apartment $apartment, Stay $stay): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', (string) ($apartment->check_in_time ?: '00:00')) + [1 => 0]);
        $arrival = CarbonImmutable::parse($stay->arrival->toDateString(), (string) config('app.timezone'))->setTime($hour, $minute);

        return CarbonImmutable::instance(Date::now())->lte($arrival->subHours($extra->lead_time_hours));
    }
}
