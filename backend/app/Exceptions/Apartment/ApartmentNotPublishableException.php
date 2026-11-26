<?php

namespace App\Exceptions\Apartment;

use App\Exceptions\DomainException;

class ApartmentNotPublishableException extends DomainException
{
    /**
     * @param  list<string>  $missing  clés des éléments manquants (photos, rates…)
     */
    public function __construct(private readonly array $missing)
    {
        parent::__construct(__('apartments.errors.not_publishable'));
    }

    protected function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'apartment_not_publishable';
    }

    public function details(): array
    {
        return [
            'missing' => array_map(fn (string $key) => [
                'key' => $key,
                'message' => __("apartments.missing.$key"),
            ], $this->missing),
        ];
    }
}
