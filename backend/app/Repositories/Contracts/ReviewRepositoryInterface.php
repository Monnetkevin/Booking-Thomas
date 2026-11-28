<?php

namespace App\Repositories\Contracts;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReviewRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Review;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Review $review, array $attributes): Review;

    /** Invitation retrouvée par l'empreinte de son jeton, verrouillée si demandé (dépôt de l'avis). */
    public function findByTokenHash(string $hash, bool $lock = false): ?Review;

    /**
     * Avis et invitations du back-office, les plus récents d'abord, avec réservation et logement.
     *
     * @return LengthAwarePaginator<int, Review>
     */
    public function paginateForAdmin(?ReviewStatus $status, ?int $apartmentId, int $perPage): LengthAwarePaginator;

    /**
     * Nombre d'avis et d'invitations par statut (filtres du back-office).
     *
     * @return array<string, int>
     */
    public function countByStatus(?int $apartmentId): array;

    /**
     * Avis publiés, les plus récents d'abord, d'un logement publié ou de tous.
     *
     * @return LengthAwarePaginator<int, Review>
     */
    public function paginatePublished(?int $apartmentId, int $perPage, int $page): LengthAwarePaginator;

    /**
     * Notes des avis publiés : nombre d'avis par note (1 à 5), d'un logement ou de tous.
     *
     * @return array<int, int>
     */
    public function publishedRatings(?int $apartmentId): array;

    /**
     * Invitations envoyées depuis ce jour et, parmi elles, celles auxquelles le voyageur a
     * répondu par un avis (quelle que soit la décision de l'hôte).
     *
     * @return array{sent: int, answered: int}
     */
    public function invitationsSince(CarbonInterface $since): array;

    /**
     * Retire le nom affiché des avis de ces réservations (le site affiche « Voyageur »).
     *
     * @param  list<int>  $bookingIds
     */
    public function clearDisplayNames(array $bookingIds): void;
}
