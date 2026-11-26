<?php

namespace App\Services\Contact;

use App\Models\ContactMessage;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Messages du formulaire de contact lus dans le back-office : liste, non lus, suivi. */
class ContactInboxService
{
    public const PER_PAGE = 20;

    public function __construct(private readonly ContactMessageRepositoryInterface $messages) {}

    /**
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    public function list(bool $unreadOnly = false): LengthAwarePaginator
    {
        return $this->messages->paginate($unreadOnly, self::PER_PAGE);
    }

    public function unreadCount(): int
    {
        return $this->messages->unreadCount();
    }

    public function setRead(ContactMessage $message, bool $read): ContactMessage
    {
        return $this->messages->setRead($message, $read);
    }
}
