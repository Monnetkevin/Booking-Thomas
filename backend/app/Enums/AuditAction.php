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
    case ApartmentPublished = 'apartment.published';
    case ApartmentUnpublished = 'apartment.unpublished';
    case ApartmentPriceUpdated = 'apartment.price_updated';
    case RatePeriodCreated = 'rate_period.created';
    case RatePeriodUpdated = 'rate_period.updated';
    case RatePeriodDeleted = 'rate_period.deleted';
    case SettingsUpdated = 'settings.updated';
    case UserInvited = 'user.invited';
    case UserUpdated = 'user.updated';
    case UserPasswordLinkSent = 'user.password_link_sent';
    case PlatformRevenuesSaved = 'platform_revenues.saved';
    /** Ancien import CSV des revenus (abandonné) : gardé pour lire le journal existant. */
    case PlatformPayoutsImported = 'platform_payouts.imported';
    case BookingsExported = 'bookings.exported';
    case ReviewPublished = 'review.published';
    case ReviewRejected = 'review.rejected';
    case ReviewReplied = 'review.replied';
    case ExtraCreated = 'extra.created';
    case ExtraUpdated = 'extra.updated';
}
