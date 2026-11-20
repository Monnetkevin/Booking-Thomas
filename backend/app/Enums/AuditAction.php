<?php

namespace App\Enums;

/** Actions sensibles du back-office, inscrites au journal d'audit (spec « Back-office »). */
enum AuditAction: string
{
    case BookingCreated = 'booking.created';
    case BookingUpdated = 'booking.updated';
    case BookingCancelled = 'booking.cancelled';
    case BlockCreated = 'block.created';
    case BlockDeleted = 'block.deleted';
    case ApartmentDeleted = 'apartment.deleted';
    case RatePeriodCreated = 'rate_period.created';
    case RatePeriodUpdated = 'rate_period.updated';
    case RatePeriodDeleted = 'rate_period.deleted';
    case SettingsUpdated = 'settings.updated';
    case UserInvited = 'user.invited';
    case UserUpdated = 'user.updated';
    case UserPasswordLinkSent = 'user.password_link_sent';
    case PlatformPayoutsImported = 'platform_payouts.imported';
    case BookingsExported = 'bookings.exported';
}
