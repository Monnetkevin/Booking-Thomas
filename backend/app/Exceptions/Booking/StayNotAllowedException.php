<?php

namespace App\Exceptions\Booking;

use App\Exceptions\DomainException;

class StayNotAllowedException extends DomainException
{
    /**
     * @param  list<array{code: string, message: string}>  $violations
     */
    public function __construct(private readonly array $violations)
    {
        parent::__construct($violations[0]['message'] ?? __('stay.not_allowed'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'stay_not_allowed';
    }

    public function details(): array
    {
        return ['violations' => $this->violations];
    }
}
