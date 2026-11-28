<?php

namespace App\Repositories\Eloquent;

use App\Models\ContactMessage;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ContactMessageRepository implements ContactMessageRepositoryInterface
{
    public function create(array $attributes): ContactMessage
    {
        return ContactMessage::query()->create($attributes);
    }

    public function paginate(bool $unreadOnly, int $perPage): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->with('apartment:id,name')
            ->when($unreadOnly, fn (Builder $query) => $query->whereNull('read_at'))
            ->latest('id')
            ->paginate($perPage);
    }

    public function unreadCount(): int
    {
        return ContactMessage::query()->whereNull('read_at')->count();
    }

    public function setRead(ContactMessage $message, bool $read): ContactMessage
    {
        if ($read !== ($message->read_at !== null)) {
            $message->forceFill(['read_at' => $read ? now() : null])->save();
        }

        return $message->load('apartment:id,name');
    }

    public function byEmail(string $email): Collection
    {
        return ContactMessage::query()->whereRaw('lower(email) = ?', [mb_strtolower($email)])->oldest('id')->get();
    }

    public function deleteByEmail(string $email): int
    {
        return ContactMessage::query()->whereRaw('lower(email) = ?', [mb_strtolower($email)])->delete();
    }
}
