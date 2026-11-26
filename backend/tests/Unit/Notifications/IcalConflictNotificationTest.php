<?php

use App\Enums\BlockType;
use App\Models\Apartment;
use App\Models\AvailabilityBlock;
use App\Notifications\Host\IcalConflictNotification;
use Illuminate\Database\Eloquent\Collection;

function conflictBlock(?string $provider): AvailabilityBlock
{
    $block = $provider === null
        ? memoryBlock(1, '2026-11-10', '2026-11-12', BlockType::Ical)->setRelation('icalFeed', null)
        : memoryIcalBlock(1, '2026-11-10', '2026-11-12', $provider);

    return $block->setRelation('apartment', (new Apartment)->forceFill(['id' => 1])->setTranslation('name', 'fr', 'Le Cocon'));
}

it('nomme la plateforme du calendrier en conflit', function (string $provider, string $label) {
    expect((new IcalConflictNotification(conflictBlock($provider), new Collection))->body())->toStartWith($label.' a transmis');
})->with([['airbnb', 'Airbnb'], ['booking', 'Booking.com']]);

it('dit « la plateforme » quand le calendrier a été supprimé, comme l’e-mail', function () {
    expect((new IcalConflictNotification(conflictBlock(null), new Collection))->body())->toStartWith('la plateforme a transmis');
});
