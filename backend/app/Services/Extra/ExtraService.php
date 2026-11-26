<?php

namespace App\Services\Extra;

use App\Enums\AuditAction;
use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\Extra;
use App\Repositories\Contracts\ExtraRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Administration des options (spec « Back-office › Options ») : catalogue, logements
 * concernés, activation, liste « À préparer » des prochaines arrivées et chiffres du mois.
 * Modifier une option ne change pas les réservations existantes (options figées).
 */
class ExtraService
{
    /** Arrivées couvertes par la liste « À préparer ». */
    public const PREPARE_DAYS = 14;

    public function __construct(
        private readonly ExtraRepositoryInterface $extras,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, Extra>
     */
    public function catalog(): Collection
    {
        return $this->extras->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function create(array $attributes, array $apartmentIds): Extra
    {
        $extra = $this->extras->create($attributes, $apartmentIds);
        $this->audit->record(AuditAction::ExtraCreated, $extra, after: $this->auditValues($extra));

        return $extra;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $apartmentIds
     */
    public function update(Extra $extra, array $attributes, array $apartmentIds): Extra
    {
        $before = $this->auditValues($extra->load('apartments'));
        $extra = $this->extras->update($extra, $attributes, $apartmentIds);
        $this->audit->record(AuditAction::ExtraUpdated, $extra, $before, $this->auditValues($extra));

        return $extra;
    }

    /**
     * Options des prochaines arrivées, à préparer : une ligne par réservation.
     *
     * @return list<array{reference: string, arrival: string, apartment: string, items: list<array{name: string, quantity: int}>}>
     */
    public function toPrepare(CarbonImmutable $today): array
    {
        return $this->extras->bookingsToPrepare($today, $today->addDays(self::PREPARE_DAYS - 1))
            ->map(fn (Booking $booking) => [
                'reference' => $booking->reference,
                'arrival' => $booking->arrival->toDateString(),
                'apartment' => $booking->apartment->getTranslation('name', 'fr'),
                'items' => $booking->extras->map(fn (BookingExtra $item) => ['name' => $item->name, 'quantity' => $item->quantity])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Chiffres de l'écran : options actives, réservations du mois avec options, montant des
     * options du mois et du mois précédent (au mois d'arrivée).
     *
     * @return array{active: int, total: int, bookings: int, with_extras: int, revenue: int, previous_revenue: int}
     */
    public function figures(CarbonImmutable $today): array
    {
        $catalog = $this->extras->all();
        $month = $today->startOfMonth();
        $current = $this->extras->monthFigures($month, $month->addMonth());
        $previous = $this->extras->monthFigures($month->subMonth(), $month);

        return [
            'active' => $catalog->where('is_active', true)->count(),
            'total' => $catalog->count(),
            'bookings' => $current['bookings'],
            'with_extras' => $current['with_extras'],
            'revenue' => $current['revenue'],
            'previous_revenue' => $previous['revenue'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Extra $extra): array
    {
        return [
            'name' => $extra->getTranslation('name', 'fr'),
            'price' => $extra->price,
            'pricing' => $extra->pricing->value,
            'max_quantity' => $extra->max_quantity,
            'lead_time_hours' => $extra->lead_time_hours,
            'is_active' => $extra->is_active,
            'apartment_ids' => $extra->apartments->modelKeys(),
        ];
    }
}
