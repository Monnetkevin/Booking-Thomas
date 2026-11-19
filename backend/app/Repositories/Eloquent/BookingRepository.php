<?php

namespace App\Repositories\Eloquent;

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
        return Booking::query()
            ->with('apartment:id,name')
            ->when($filters->status, fn (Builder $query, $status) => $query->where('status', $status->value))
            ->when($filters->apartmentId, fn (Builder $query, $id) => $query->where('apartment_id', $id))
            ->when($filters->source, fn (Builder $query, $source) => $query->where('source', $source->value))
            // Séjours qui touchent [from, to[ : arrivée avant la fin, départ après le début.
            ->when($filters->to, fn (Builder $query, $to) => $query->where('arrival', '<', $to))
            ->when($filters->from, fn (Builder $query, $from) => $query->where('departure', '>', $from))
            ->when($filters->search, function (Builder $query, string $search): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(fn (Builder $q) => $q->where('reference', 'ilike', $like)
                    ->orWhere('guest_name', 'ilike', $like)
                    ->orWhere('guest_email', 'ilike', $like));
            })
            ->orderBy('arrival')
            ->orderBy('id')
            ->paginate($perPage);
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
}
