<?php

namespace App\Repositories\Contracts;

use App\Models\ContactMessage;

interface ContactMessageRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContactMessage;
}
