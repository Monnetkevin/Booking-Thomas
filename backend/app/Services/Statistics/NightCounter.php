<?php

namespace App\Services\Statistics;

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use App\Models\PlatformPayout;
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
     * Nuits des séjours importés par iCal qui arrivent dans [from, to[, par plateforme
     * (« airbnb », « booking »), comptées comme leurs revenus : au mois d'arrivée. Une nuit
     * tenue par deux séjours en conflit ne compte qu'une fois, comme dans count().
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks  blocs actifs (calendrier chargé)
     * @return array<string, int>
     */
    public function platformNightsArriving(Collection $blocks, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $arriving = $blocks->filter(fn (AvailabilityBlock $block) => $this->kind($block) === 'platform'
            && $block->icalFeed !== null && $block->starts_on->gte($from) && $block->starts_on->lt($to));
        if ($arriving->isEmpty()) {
            return [];
        }
        // Propriétaires des nuits jusqu'au dernier départ : un séjour arrivé dans la période
        // compte toutes ses nuits, même celles d'après.
        $owners = $this->nightOwners($blocks, $from, $arriving->max(fn (AvailabilityBlock $block) => $block->ends_on));

        $nights = [];
        foreach ($arriving as $block) {
            $provider = $block->icalFeed->provider->value;
            for ($day = $block->starts_on; $day->lt($block->ends_on); $day = $day->addDay()) {
                if ($owners[$block->apartment_id.'|'.$day->toDateString()]['block'] === spl_object_id($block)) {
                    $nights[$provider] = ($nights[$provider] ?? 0) + 1;
                }
            }
        }

        return $nights;
    }

    /**
     * Pour tous les chiffres (voir SoldStayBlocks) : un bloc d'un calendrier qui ne distingue pas séjours et dates
     * fermées (Booking.com) n'est un séjour vendu que si un versement importé de cette
     * plateforme arrive le même jour dans le même logement ; sinon il compte comme fermé.
     *
     * @param  Collection<int, AvailabilityBlock>  $blocks
     * @param  Collection<int, PlatformPayout>  $payouts
     * @return Collection<int, AvailabilityBlock>
     */
    public function confirmPlatformStays(Collection $blocks, Collection $payouts): Collection
    {
        $paid = $payouts->mapWithKeys(fn (PlatformPayout $payout) => [$payout->platform->value.'|'.$payout->apartment_id.'|'.$payout->arrival->toDateString() => true]);

        return $blocks->map(function (AvailabilityBlock $block) use ($paid): AvailabilityBlock {
            $provider = $block->type === BlockType::Ical ? $block->icalFeed?->provider : null;
            if ($provider === null || ! $provider->confirmsStaysByPayout() || $paid->has($provider->value.'|'.$block->apartment_id.'|'.$block->starts_on->toDateString())) {
                return $block;
            }

            // Copie non enregistrée, comptée comme une fermeture.
            return $block->replicate()->forceFill(['type' => BlockType::Manual]);
        });
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
