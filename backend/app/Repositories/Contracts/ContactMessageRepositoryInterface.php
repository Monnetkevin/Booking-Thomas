<?php

namespace App\Repositories\Contracts;

use App\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ContactMessageRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContactMessage;

    /**
     * Messages, les plus récents d'abord, avec le logement concerné.
     *
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    public function paginate(bool $unreadOnly, int $perPage): LengthAwarePaginator;

    public function unreadCount(): int;

    /** Marque le message lu ou non lu ; renvoyé avec son logement. */
    public function setRead(ContactMessage $message, bool $read): ContactMessage;

    /**
     * Messages envoyés avec cette adresse (sans tenir compte de la casse).
     *
     * @return Collection<int, ContactMessage>
     */
    public function byEmail(string $email): Collection;

    public function deleteByEmail(string $email): int;
}
