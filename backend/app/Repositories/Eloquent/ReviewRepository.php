<?php

namespace App\Repositories\Eloquent;

use App\Enums\ApartmentStatus;
use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function create(array $attributes): Review
    {
        return Review::query()->create($attributes);
    }

    public function update(Review $review, array $attributes): Review
    {
        $review->update($attributes);

        return $review;
    }

    public function findByTokenHash(string $hash, bool $lock = false): ?Review
    {
        return Review::query()
            ->where('token_hash', $hash)
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())
            ->with(['booking', 'apartment'])
            ->first();
    }

    public function paginateForAdmin(?ReviewStatus $status, ?int $apartmentId, int $perPage): LengthAwarePaginator
    {
        return Review::query()
            ->when($status, fn (Builder $query, ReviewStatus $status) => $query->where('status', $status->value))
            ->when($apartmentId, fn (Builder $query, int $id) => $query->where('apartment_id', $id))
            ->with(['booking', 'apartment'])
            ->orderByRaw('coalesce(submitted_at, invited_at) desc')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function countByStatus(?int $apartmentId): array
    {
        /** @var array<string, int> */
        return Review::query()
            ->when($apartmentId, fn (Builder $query, int $id) => $query->where('apartment_id', $id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function paginatePublished(?int $apartmentId, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->published($apartmentId)
            ->with(['booking', 'apartment'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage, page: $page);
    }

    public function publishedRatings(?int $apartmentId): array
    {
        /** @var array<int, int> */
        return $this->published($apartmentId)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function invitationsSince(CarbonInterface $since): array
    {
        $row = Review::query()
            ->where('invited_at', '>=', $since)
            ->selectRaw('count(*) as sent, count(submitted_at) as answered')
            ->first();

        return ['sent' => (int) ($row->sent ?? 0), 'answered' => (int) ($row->answered ?? 0)];
    }

    /**
     * Avis publiés d'un logement encore en ligne (un logement supprimé ou en brouillon n'en montre plus).
     *
     * @return Builder<Review>
     */
    private function published(?int $apartmentId): Builder
    {
        return Review::query()
            ->where('status', ReviewStatus::Published)
            ->whereHas('apartment', fn (Builder $query) => $query->whereNull('deleted_at')->where('status', ApartmentStatus::Published))
            ->when($apartmentId, fn (Builder $query, int $id) => $query->where('apartment_id', $id));
    }
}
