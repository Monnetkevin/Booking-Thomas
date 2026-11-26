<?php

namespace App\Providers;

use App\Repositories\Contracts\AmenityRepositoryInterface;
use App\Repositories\Contracts\ApartmentPhotoRepositoryInterface;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use App\Repositories\Contracts\ExtraRepositoryInterface;
use App\Repositories\Contracts\IcalFeedRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PlatformRevenueRepositoryInterface;
use App\Repositories\Contracts\RatePeriodRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AmenityRepository;
use App\Repositories\Eloquent\ApartmentPhotoRepository;
use App\Repositories\Eloquent\ApartmentRepository;
use App\Repositories\Eloquent\AuditLogRepository;
use App\Repositories\Eloquent\AvailabilityRepository;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Eloquent\ContactMessageRepository;
use App\Repositories\Eloquent\ExtraRepository;
use App\Repositories\Eloquent\IcalFeedRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Repositories\Eloquent\PlatformRevenueRepository;
use App\Repositories\Eloquent\RatePeriodRepository;
use App\Repositories\Eloquent\ReviewRepository;
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
        PlatformRevenueRepositoryInterface::class => PlatformRevenueRepository::class,
        BookingRepositoryInterface::class => BookingRepository::class,
        ApartmentRepositoryInterface::class => ApartmentRepository::class,
        ApartmentPhotoRepositoryInterface::class => ApartmentPhotoRepository::class,
        AmenityRepositoryInterface::class => AmenityRepository::class,
        RatePeriodRepositoryInterface::class => RatePeriodRepository::class,
        AvailabilityRepositoryInterface::class => AvailabilityRepository::class,
        ContactMessageRepositoryInterface::class => ContactMessageRepository::class,
        IcalFeedRepositoryInterface::class => IcalFeedRepository::class,
        SettingRepositoryInterface::class => SettingRepository::class,
        NotificationRepositoryInterface::class => NotificationRepository::class,
        AuditLogRepositoryInterface::class => AuditLogRepository::class,
        ReviewRepositoryInterface::class => ReviewRepository::class,
        ExtraRepositoryInterface::class => ExtraRepository::class,
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
