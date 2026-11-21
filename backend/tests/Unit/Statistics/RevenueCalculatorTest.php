<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Statistics\RevenueCalculator;

function memoryPaidBooking(BookingStatus $status, int $paid, int $refunded, int $tax): Booking
{
    return memoryBooking(1, '2026-11-02', '2026-11-04', $status, ['tourist_tax' => $tax, 'paid_amount' => $paid, 'refunded_amount' => $refunded]);
}

it('compte l’argent encaissé moins les remboursements, hors taxe de séjour', function () {
    expect((new RevenueCalculator)->ofBooking(memoryPaidBooking(BookingStatus::Confirmed, 50000, 5000, 1000)))->toBe(44000);
});

it('ne retire pas la taxe de séjour d’une annulation : elle n’est pas due', function () {
    expect((new RevenueCalculator)->ofBooking(memoryPaidBooking(BookingStatus::Cancelled, 40000, 25000, 1000)))->toBe(15000);
});

it('ne rapporte rien sans paiement, jamais un revenu négatif', function () {
    $calculator = new RevenueCalculator;

    expect($calculator->ofBooking(memoryPaidBooking(BookingStatus::Confirmed, 0, 0, 1000)))->toBe(0)
        ->and($calculator->ofBooking(memoryPaidBooking(BookingStatus::Cancelled, 10000, 12000, 0)))->toBe(0)
        ->and($calculator->of(collect([
            memoryPaidBooking(BookingStatus::Confirmed, 20000, 0, 500),
            memoryPaidBooking(BookingStatus::Completed, 30000, 0, 0),
        ])))->toBe(49500);
});
