<?php

namespace App\Services\Planning;

use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Services\Statistics\NightCounter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Chiffres de la période affichée au planning, avec les mêmes règles que le tableau de bord
 * et les statistiques (NightCounter) : nuits vendues et ouvertes, occupation, arrivées dont
 * directes, nuits libres dont de week-end (vendredi et samedi). Les blocs reçus sont déjà
 * passés par SoldStayBlocks (dates Booking.com répétant une fermeture du site comptées fermées).
 */
class PlanningFigures
{
    public function __construct(private readonly NightCounter $nights) {}

    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs des logements affichés
     * @param  EloquentCollection<int, Apartment>  $apartments  logements affichés : seuls ceux sur le marché comptent
     * @return array{sold_nights: int, open_nights: int, occupancy: float|null, arrivals: int, direct_arrivals: int, free_nights: int, free_weekend_nights: int}
     */
    public function compute(Collection $blocks, EloquentCollection $apartments, CarbonImmutable $from, CarbonImmutable $to): array
    {
        // Brouillons hors chiffres : pas encore à la vente.
        $market = $this->nights->onMarket($apartments);
        $blocks = $blocks->whereIn('apartment_id', $market->modelKeys());
        $nights = $this->nights->count($blocks, $from, $to);
        $arrivals = $this->nights->arrivals($blocks, $from, $to);

        $free = 0;
        $freeWeekend = 0;
        foreach ($market as $apartment) {
            $own = $blocks->filter(fn (AvailabilityBlock $block) => $block->apartment_id === $apartment->id);
            // Pas de nuit libre avant la création du logement.
            $start = $apartment->created_at === null ? $from : $from->max(CarbonImmutable::parse($apartment->created_at)->startOfDay());
            for ($day = $start; $day->lt($to); $day = $day->addDay()) {
                // Une réservation en attente de paiement ne prend pas la nuit : libres + vendues
                // + fermées = nuits de la période, comme les autres chiffres.
                $taken = $own->contains(fn (AvailabilityBlock $block) => $block->starts_on->lte($day) && $block->ends_on->gt($day) && $this->nights->kind($block) !== null);
                if (! $taken) {
                    $free++;
                    $freeWeekend += in_array($day->dayOfWeek, [CarbonImmutable::FRIDAY, CarbonImmutable::SATURDAY], true) ? 1 : 0;
                }
            }
        }

        return [
            'sold_nights' => $nights['direct'] + $nights['platform'],
            'open_nights' => $this->nights->openNights($nights, $this->nights->marketNights($market, $from, $to)),
            'occupancy' => $this->nights->occupancy($nights, $this->nights->marketNights($market, $from, $to)),
            'arrivals' => $arrivals['all'],
            'direct_arrivals' => $arrivals['direct'],
            'free_nights' => $free,
            'free_weekend_nights' => $freeWeekend,
        ];
    }
}
