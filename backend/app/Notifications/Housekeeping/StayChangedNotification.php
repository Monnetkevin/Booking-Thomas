<?php

namespace App\Notifications\Housekeeping;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Models\Apartment;
use App\Notifications\BackOfficeNotification;
use App\Services\Housekeeping\StayChange;
use App\Support\StayDates;

/**
 * Séjour ajouté, annulé ou déplacé au planning du ménage. Ni nom, ni e-mail, ni montant,
 * ni source : logement, dates, nombre de voyageurs s'il est connu.
 */
class StayChangedNotification extends BackOfficeNotification
{
    public function __construct(
        public readonly StayChange $change,
        public readonly Apartment $apartment,
        public readonly bool $urgent,
        public readonly bool $sameDayTurnover,
    ) {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::StayChanged;
    }

    public function level(): NotificationLevel
    {
        return $this->urgent ? NotificationLevel::Urgent : NotificationLevel::Info;
    }

    public function title(): string
    {
        return __('notifications.stay.'.$this->change->kind->value, ['apartment' => $this->apartment->getTranslation('name', 'fr')], 'fr');
    }

    public function body(): string
    {
        $lines = [__('notifications.stay.dates', [
            'arrival' => StayDates::day($this->change->startsOn, 'fr'),
            'departure' => StayDates::day($this->change->endsOn, 'fr'),
        ], 'fr')];
        if ($this->change->previousStartsOn !== null && $this->change->previousEndsOn !== null) {
            $lines[] = __('notifications.stay.previous', [
                'arrival' => StayDates::day($this->change->previousStartsOn, 'fr'),
                'departure' => StayDates::day($this->change->previousEndsOn, 'fr'),
            ], 'fr');
        }
        if ($this->change->guests !== null) {
            $lines[] = __('notifications.stay.guests', ['count' => $this->change->guests], 'fr');
        }
        if ($this->sameDayTurnover) {
            $lines[] = __('notifications.stay.turnover', [], 'fr');
        }

        return implode(' ', $lines);
    }

    public function link(): string
    {
        return '/admin/notifications';
    }

    public function dedupeKey(): string
    {
        return 'block:'.$this->change->blockId;
    }
}
