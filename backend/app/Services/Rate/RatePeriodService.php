<?php

namespace App\Services\Rate;

use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Périodes tarifaires d'un logement (dates incluses). Deux périodes d'un même logement
 * ne se recouvrent jamais ; modifier un tarif ne change pas les réservations déjà faites,
 * dont le détail du prix est figé.
 */
class RatePeriodService
{
    public function __construct(private readonly RatePeriodRepositoryInterface $periods) {}

    /**
     * @return Collection<int, RatePeriod>
     */
    public function list(Apartment $apartment, bool $includePast = false): Collection
    {
        return $this->periods->forApartment($apartment, $includePast);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(Apartment $apartment, array $data): RatePeriod
    {
        $this->ensureNoOverlap($apartment->id, $data['starts_on'], $data['ends_on']);

        return $this->guarded(fn () => $this->periods->create($apartment, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(RatePeriod $period, array $data): RatePeriod
    {
        $this->ensureNoOverlap($period->apartment_id, $data['starts_on'], $data['ends_on'], $period->id);

        return $this->guarded(fn () => $this->periods->update($period, $data));
    }

    public function delete(RatePeriod $period): void
    {
        $this->periods->delete($period);
    }

    /**
     * @throws ValidationException
     */
    private function ensureNoOverlap(int $apartmentId, string $from, string $to, ?int $exceptId = null): void
    {
        $overlapping = $this->periods->findOverlapping($apartmentId, $from, $to, $exceptId);

        if ($overlapping) {
            throw ValidationException::withMessages(['starts_on' => $this->overlapMessage($overlapping)]);
        }
    }

    /**
     * Filet de sécurité si deux enregistrements simultanés passent la vérification :
     * la contrainte d'exclusion PostgreSQL refuse le second.
     *
     * @param  callable(): RatePeriod  $write
     *
     * @throws ValidationException
     */
    private function guarded(callable $write): RatePeriod
    {
        try {
            /** @var RatePeriod */
            return DB::transaction(fn () => $write());
        } catch (QueryException $e) {
            if ($e->getCode() === '23P01' && str_contains($e->getMessage(), 'rate_periods_no_overlap')) {
                throw ValidationException::withMessages(['starts_on' => __('rates.overlap_generic')]);
            }
            throw $e;
        }
    }

    private function overlapMessage(RatePeriod $period): string
    {
        return __('rates.overlap', [
            'label' => $period->label ?: __('rates.unnamed'),
            'from' => $period->starts_on->translatedFormat('j F Y'),
            'to' => $period->ends_on->translatedFormat('j F Y'),
        ]);
    }
}
