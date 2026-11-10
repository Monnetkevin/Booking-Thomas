<?php

namespace App\Repositories\Contracts;

interface BookingRepositoryInterface
{
    public function referenceExists(string $reference): bool;
}
