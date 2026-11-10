<?php

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Booking\BookingReferenceGenerator;

it('génère une référence publique au bon format', function () {
    expect(app(BookingReferenceGenerator::class)->generate())->toMatch('/^BT-[A-Z0-9]{8}$/');
});

it('ne réutilise jamais une référence existante', function () {
    $taken = Booking::factory()->create()->reference;
    $bookings = Mockery::mock(BookingRepositoryInterface::class);
    $bookings->shouldReceive('referenceExists')->twice()->andReturn(true, false);

    $reference = (new BookingReferenceGenerator($bookings))->generate();

    expect($reference)->not->toBe($taken)->toMatch('/^BT-[A-Z0-9]{8}$/');
});
