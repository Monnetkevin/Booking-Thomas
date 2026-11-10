<?php

namespace App\Providers;

use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\ApartmentRepository;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Eloquent\SettingRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\AuthService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\ServiceProvider;

/**
 * Liaison des interfaces de repositories à leurs implémentations Eloquent.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        UserRepositoryInterface::class => UserRepository::class,
        BookingRepositoryInterface::class => BookingRepository::class,
        ApartmentRepositoryInterface::class => ApartmentRepository::class,
        SettingRepositoryInterface::class => SettingRepository::class,
    ];

    public function register(): void
    {
        $this->app->when(AuthService::class)
            ->needs(StatefulGuard::class)
            ->give(function ($app): StatefulGuard {
                /** @var StatefulGuard */
                return $app->make(AuthFactory::class)->guard('web');
            });
    }
}
