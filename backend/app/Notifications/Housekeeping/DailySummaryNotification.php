<?php

namespace App\Notifications\Housekeeping;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Notifications\BackOfficeNotification;
use App\Support\StayDates;
use Carbon\CarbonImmutable;

/** Départs et arrivées du lendemain, par logement ; envoyé à l'heure choisie par le compte. */
class DailySummaryNotification extends BackOfficeNotification
{
    /**
     * @param  list<string>  $movements  une ligne par départ ou arrivée
     */
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly array $movements,
    ) {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::DailySummary;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Info;
    }

    public function title(): string
    {
        return __('notifications.daily_summary.title', ['date' => StayDates::day($this->date, 'fr'), 'count' => count($this->movements)], 'fr');
    }

    public function body(): string
    {
        return __('notifications.daily_summary.body', [], 'fr');
    }

    public function details(): array
    {
        return $this->movements;
    }

    public function link(): string
    {
        return '/admin/notifications';
    }

    public function dedupeKey(): string
    {
        return 'summary:'.$this->date->toDateString();
    }
}
