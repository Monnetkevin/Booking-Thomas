<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStage;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Booking\BookingFilters;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function referenceExists(string $reference): bool
    {
        return Booking::query()->where('reference', $reference)->exists();
    }

    public function create(array $attributes): Booking
    {
        return Booking::query()->create($attributes);
    }

    public function update(Booking $booking, array $attributes): Booking
    {
        $booking->update($attributes);

        return $booking;
    }

    public function transitionFromPending(Booking $booking, array $attributes): bool
    {
        $changed = Booking::query()
            ->whereKey($booking->getKey())
            ->where('status', BookingStatus::Pending)
            ->update($attributes) === 1;

        $booking->refresh();

        return $changed;
    }

    public function findByReference(string $reference): ?Booking
    {
        return Booking::query()->where('reference', $reference)->first();
    }

    public function overduePending(CarbonInterface $now): Collection
    {
        return Booking::query()
            ->where('status', BookingStatus::Pending)
            ->where('expires_at', '<', $now)
            ->get();
    }

    public function paginateForAdmin(BookingFilters $filters, int $perPage): LengthAwarePaginator
    {
        return $this->adminQuery($filters)->paginate($perPage);
    }

    public function allForAdmin(BookingFilters $filters, int $limit): Collection
    {
        return $this->adminQuery($filters)->limit($limit)->get();
    }

    public function countByStage(BookingFilters $filters): array
    {
        $base = $filters->withoutStage();
        $counts = ['all' => $this->filtered($base)->count()];
        foreach (BookingStage::cases() as $stage) {
            $counts[$stage->value] = $this->filtered($base->withStage($stage))->count();
        }

        return $counts;
    }

    /**
     * Liste du back-office : filtres, sommes payées et remboursées, arrivées les plus proches d'abord.
     *
     * @return Builder<Booking>
     */
    private function adminQuery(BookingFilters $filters): Builder
    {
        $succeeded = fn (PaymentType $type) => fn (Builder $query) => $query->where('status', PaymentStatus::Succeeded)->where('type', $type);

        return $this->filtered($filters)
            ->with('apartment:id,name')
            ->withSum(['payments as paid_amount' => $succeeded(PaymentType::Payment)], 'amount')
            ->withSum(['payments as refunded_amount' => $succeeded(PaymentType::Refund)], 'amount')
            ->orderBy('arrival')
            ->orderBy('id');
    }

    /**
     * @return Builder<Booking>
     */
    private function filtered(BookingFilters $filters): Builder
    {
        $sold = [BookingStatus::Confirmed->value, BookingStatus::Completed->value];
        $today = $filters->today ?? now()->toDateString();

        return Booking::query()
            ->when($filters->status, fn (Builder $query, $status) => $query->where('status', $status->value))
            ->when($filters->apartmentId, fn (Builder $query, $id) => $query->where('apartment_id', $id))
            ->when($filters->source, fn (Builder $query, $source) => $query->where('source', $source->value))
            // Séjours qui touchent [from, to[ : arrivée avant la fin, départ après le début.
            ->when($filters->to, fn (Builder $query, $to) => $query->where('arrival', '<', $to))
            ->when($filters->from, fn (Builder $query, $from) => $query->where('departure', '>', $from))
            ->when($filters->search, fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $this->matching($q, $search)))
            ->when($filters->stage, fn (Builder $query, BookingStage $stage) => match ($stage) {
                BookingStage::Upcoming => $query->where('status', BookingStatus::Confirmed)->where('arrival', '>', $today),
                BookingStage::Ongoing => $query->whereIn('status', $sold)->where('arrival', '<=', $today)->where('departure', '>', $today),
                BookingStage::Past => $query->whereIn('status', $sold)->where('departure', '<=', $today),
                BookingStage::Cancelled => $query->where('status', BookingStatus::Cancelled),
            });
    }

    public function search(string $term, int $limit): Collection
    {
        return Booking::query()
            ->with('apartment:id,name')
            ->where(fn (Builder $query) => $this->matching($query, $term))
            ->orderByDesc('arrival')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Référence, nom ou e-mail du voyageur, sans jokers SQL venus de la saisie.
     *
     * @param  Builder<Booking>  $query
     */
    private function matching(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        $query->where('reference', 'ilike', $like)
            ->orWhere('guest_name', 'ilike', $like)
            ->orWhere('guest_email', 'ilike', $like);
    }

    public function loadDetails(Booking $booking): Booking
    {
        return $booking->load(['apartment', 'payments' => fn ($query) => $query->orderBy('id')]);
    }

    public function cancelIfConfirmed(Booking $booking): bool
    {
        $attributes = ['status' => BookingStatus::Cancelled, 'cancelled_at' => now()];
        $updated = Booking::query()->whereKey($booking->id)->where('status', BookingStatus::Confirmed)->update($attributes);
        if ($updated === 1) {
            $booking->forceFill($attributes)->syncOriginal();

            return true;
        }
        $booking->refresh();

        return false;
    }

    public function recordOfflinePayment(Booking $booking, int $amount): void
    {
        $booking->payments()->create(['type' => PaymentType::Payment, 'amount' => $amount, 'status' => PaymentStatus::Succeeded]);
    }

    public function soldArrivingBetween(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Booking::query()
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->where('arrival', '>=', $from->toDateString())
            ->where('arrival', '<', $to->toDateString())
            ->get();
    }
}
