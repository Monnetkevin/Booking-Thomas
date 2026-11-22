<?php

namespace App\Services\Statistics;

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Compte les nuits d'une période [from, to[ à partir des blocs actifs : vendues en direct
 * (réservations confirmées ou terminées), vendues sur une plateforme (séjours importés),
 * fermées (blocages manuels, dates fermées sur une plateforme). Une réservation en attente de paiement n'est pas une vente.
 */
class NightCounter
{
    /**
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs (réservation chargée)
     * @return array{direct: int, platform: int, closed: int}
     */
    public function count(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $nights = ['direct' => 0, 'platform' => 0, 'closed' => 0];
        foreach ($this->nightOwners($blocks, $from, $to) as ['kind' => $kind]) {
            $nights[$kind]++;
        }

        return $nights;
    }

    /**
     * Bloc retenu pour chaque nuit (logement|date) de [from, to[ : une nuit couverte par
     * plusieurs blocs (conflit, même date fermée ici et sur une plateforme) ne compte qu'une
     * fois, pour le séjour du site, puis le séjour plateforme, puis la fermeture ; à rang égal,
     * pour le premier bloc.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     * @return array<string, array{kind: string, block: int}>
     */
    private function nightOwners(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rank = ['closed' => 1, 'platform' => 2, 'direct' => 3];
        $owners = [];
        foreach ($blocks as $block) {
            $kind = $this->kind($block);
            if ($kind === null) {
                continue;
            }
            $end = $block->ends_on->min($to);
            for ($day = $block->starts_on->max($from); $day->lt($end); $day = $day->addDay()) {
                $key = $block->apartment_id.'|'.$day->toDateString();
                if (! isset($owners[$key]) || $rank[$kind] > $rank[$owners[$key]['kind']]) {
                    $owners[$key] = ['kind' => $kind, 'block' => spl_object_id($block)];
                }
            }
        }

        return $owners;
    }

    /**
     * Nuits vendues sur chaque plateforme dans [from, to[, par logement : la base des revenus
     * saisis chaque mois (PlatformRevenueService). Une nuit tenue par deux séjours en conflit ne
     * compte qu'une fois, comme dans count().
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs (calendrier chargé)
     * @return array<int, array<string, int>> id du logement → plateforme (« airbnb », « booking ») → nuits
     */
    public function platformNightsByApartment(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $byBlock = $blocks->keyBy(fn (AvailabilityBlock $block) => spl_object_id($block));
        $nights = [];
        foreach ($this->nightOwners($blocks, $from, $to) as ['kind' => $kind, 'block' => $id]) {
            $block = $byBlock[$id];
            $provider = $block->icalFeed?->provider->value;
            if ($kind !== 'platform' || $provider === null) {
                continue;
            }
            $nights[$block->apartment_id][$provider] = ($nights[$block->apartment_id][$provider] ?? 0) + 1;
        }

        return $nights;
    }

    /**
     * Un bloc qui ne fait que répéter une fermeture du site (date Booking.com posée sur une
     * fermeture) compte comme fermé (copie non enregistrée), les autres restent des séjours.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     * @return Collection<int, AvailabilityBlock>
     */
    public function closeRedundantClosures(Collection $blocks): Collection
    {
        return $blocks->map(fn (AvailabilityBlock $block): AvailabilityBlock => $block->isRedundantClosure()
            ? $block->replicate()->forceFill(['type' => BlockType::Manual])
            : $block);
    }

    /** Nuits du bloc qui tombent dans [from, to[. */
    public function overlap(AvailabilityBlock $block, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $start = $block->starts_on->max($from);
        $end = $block->ends_on->min($to);

        return $end->gt($start) ? (int) $start->diffInDays($end) : 0;
    }

    /** direct, platform, closed ; null pour une réservation qui n'est pas (ou plus) vendue. */
    public function kind(AvailabilityBlock $block): ?string
    {
        return match ($block->type) {
            BlockType::Ical => $block->icalFeed?->provider->isClosure($block->note) ? 'closed' : 'platform',
            BlockType::Manual => 'closed',
            BlockType::Booking => in_array($block->booking?->status, [BookingStatus::Confirmed, BookingStatus::Completed], true) ? 'direct' : null,
        };
    }

    /**
     * Taux d'occupation : nuits vendues sur nuits ouvertes (les nuits fermées ne comptent
     * pas comme disponibles). Null s'il n'y avait aucune nuit ouverte.
     *
     * @param  array{direct: int, platform: int, closed: int}  $nights
     */
    public function occupancy(array $nights, int $apartments, int $days): ?float
    {
        $open = $apartments * $days - $nights['closed'];

        return $open > 0 ? round(($nights['direct'] + $nights['platform']) / $open * 100, 1) : null;
    }

    /**
     * Séjours vendus (site et plateformes) qui arrivent dans [from, to[, et ceux du site.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     * @return array{all: int, direct: int}
     */
    public function arrivals(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        // Un séjour compte s'il tient sa première nuit : deux séjours en conflit sur les mêmes
        // nuits font une arrivée, comme ils font une seule série de nuits vendues.
        $owners = $this->nightOwners($blocks, $from, $to);
        $kinds = $blocks
            ->filter(fn (AvailabilityBlock $block) => $block->starts_on->gte($from) && $block->starts_on->lt($to))
            ->filter(fn (AvailabilityBlock $block) => ($owners[$block->apartment_id.'|'.$block->starts_on->toDateString()]['block'] ?? null) === spl_object_id($block))
            ->map(fn (AvailabilityBlock $block) => $this->kind($block))
            ->filter(fn (?string $kind) => in_array($kind, ['direct', 'platform'], true));

        return ['all' => $kinds->count(), 'direct' => $kinds->filter(fn (string $kind) => $kind === 'direct')->count()];
    }

    /**
     * Nuits ouvertes à la vente : nuits de la période moins les fermetures.
     *
     * @param  array{direct: int, platform: int, closed: int}  $nights
     */
    public function openNights(array $nights, int $apartments, int $days): int
    {
        return $apartments * $days - $nights['closed'];
    }
}
