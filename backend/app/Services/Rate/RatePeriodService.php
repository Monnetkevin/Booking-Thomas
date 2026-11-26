<?php

namespace App\Services\Rate;

use App\Enums\AuditAction;
use App\Models\Apartment;
use App\Models\RatePeriod;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\CatalogCache;
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
    public function __construct(
        private readonly RatePeriodRepositoryInterface $periods,
        private readonly CatalogCache $catalog,
        private readonly AuditLogger $audit,
    ) {}

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

        $period = $this->guarded(fn () => $this->periods->create($apartment, $data));
        $this->audit->record(AuditAction::RatePeriodCreated, $period, after: $this->auditValues($period));

        return $period;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(RatePeriod $period, array $data): RatePeriod
    {
        $this->ensureNoOverlap($period->apartment_id, $data['starts_on'], $data['ends_on'], $period->id);

        $before = $this->auditValues($period);
        $period = $this->guarded(fn () => $this->periods->update($period, $data));
        $this->audit->record(AuditAction::RatePeriodUpdated, $period, $before, $this->auditValues($period));

        return $period;
    }

    public function delete(RatePeriod $period): void
    {
        $before = $this->auditValues($period);
        $this->periods->delete($period);
        $this->audit->record(AuditAction::RatePeriodDeleted, $period, before: $before);
        $this->catalog->flush();
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
            /** @var RatePeriod $period */
            $period = DB::transaction(fn () => $write());
            $this->catalog->flush();

            return $period;
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

    /**
     * Ce que le journal retient d'une période : logement, dates et prix.
     *
     * @return array<string, mixed>
     */
    private function auditValues(RatePeriod $period): array
    {
        return [
            'apartment_id' => $period->apartment_id,
            'label' => $period->label,
            'starts_on' => $period->starts_on->toDateString(),
            'ends_on' => $period->ends_on->toDateString(),
            'nightly_price' => $period->nightly_price,
            'min_stay' => $period->min_stay,
            'weekend_supplement' => $period->weekend_supplement,
        ];
    }
}
