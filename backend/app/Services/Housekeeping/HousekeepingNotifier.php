<?php

namespace App\Services\Housekeeping;

use App\Enums\BlockType;
use App\Enums\StayChangeKind;
use App\Enums\UserRole;
use App\Models\AvailabilityBlock;
use App\Models\User;
use App\Notifications\Housekeeping\DailySummaryNotification;
use App\Notifications\Housekeeping\StayChangedNotification;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Repositories\Contracts\AvailabilityRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationPreferences;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Support\Facades\Date;

/**
 * Prévient le ménage de ce qui change son planning (spec « Notifications ») :
 * - un séjour ajouté, annulé ou déplacé dont une arrivée ou un départ tombe dans les
 *   14 prochains jours ; urgent si c'est aujourd'hui ou demain ;
 * - chaque jour, à l'heure choisie par chaque compte, les départs et arrivées du lendemain.
 */
class HousekeepingNotifier
{
    /** Jours couverts par le planning du ménage (spec). */
    public const HORIZON_DAYS = 14;

    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly Dispatcher $notifier,
        private readonly UserRepositoryInterface $users,
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly AvailabilityRepositoryInterface $availability,
    ) {}

    public function stayChanged(StayChange $change): void
    {
        $today = CarbonImmutable::instance(Date::today());
        $horizon = $today->addDays(self::HORIZON_DAYS);
        $days = array_filter($change->days(), fn (CarbonImmutable $day) => $day->betweenIncluded($today, $horizon));
        $apartment = $this->apartments->findWithTrashed($change->apartmentId);
        if ($days === [] || $apartment === null) {
            return;
        }

        $urgent = array_filter($days, fn (CarbonImmutable $day) => $day->lte($today->addDay())) !== [];
        $turnover = $change->kind !== StayChangeKind::Cancelled && $this->availability->hasSameDayTurnover(
            $change->apartmentId, $change->startsOn->toDateString(), $change->endsOn->toDateString(), $change->blockId,
        );

        $this->notifications->dispatch(new StayChangedNotification($change, $apartment, $urgent, $turnover));
    }

    /**
     * Récapitulatif aux comptes ménage dont c'est l'heure ; rien s'il n'y a aucun mouvement.
     *
     * @return int nombre de comptes prévenus
     */
    public function sendDailySummaries(CarbonImmutable $now): int
    {
        $recipients = $this->users->activeByRole(UserRole::Housekeeping)->filter(function (User $user) use ($now): bool {
            $preferences = NotificationPreferences::fromStored($user->role, $user->notification_preferences);

            return $preferences->summaryEnabled && $preferences->summaryHour === $now->hour;
        });
        if ($recipients->isEmpty()) {
            return 0;
        }

        $tomorrow = $now->startOfDay()->addDay();
        $movements = $this->movements($tomorrow);
        if ($movements === []) {
            return 0;
        }

        $this->notifier->send($recipients, new DailySummaryNotification($tomorrow, $movements));

        return $recipients->count();
    }

    /**
     * Une ligne par départ, arrivée ou fermeture du jour, sans donnée personnelle.
     *
     * @return list<string>
     */
    private function movements(CarbonImmutable $date): array
    {
        $lines = [];
        foreach ($this->availability->movementsOn($date->toDateString()) as $block) {
            $line = $this->movement($block, $date);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function movement(AvailabilityBlock $block, CarbonImmutable $date): ?string
    {
        $apartment = $block->apartment->getTranslation('name', 'fr');
        $departing = $block->ends_on->isSameDay($date);

        if ($block->type === BlockType::Manual && $block->needs_cleaning) {
            // Logement occupé pendant la fermeture (famille, amis) : ménage à la fin.
            return __('notifications.daily_summary.'.($departing ? 'occupied_end' : 'occupied'), ['apartment' => $apartment], 'fr');
        }
        if ($block->isClosure()) {
            // Une fermeture (ici ou sur une plateforme) qui se termine ne demande rien au ménage.
            return $departing ? null : __('notifications.daily_summary.blocked', ['apartment' => $apartment], 'fr');
        }

        if ($departing) {
            return __('notifications.daily_summary.departure', ['apartment' => $apartment, 'time' => $this->time($block->apartment->check_out_time)], 'fr');
        }

        $guests = $block->booking !== null ? $block->booking->adults + $block->booking->children : null;

        return __('notifications.daily_summary.arrival', [
            'apartment' => $apartment,
            'time' => $this->time($block->apartment->check_in_time),
            'guests' => $guests === null ? '' : __('notifications.daily_summary.arrival_guests', ['count' => $guests], 'fr'),
        ], 'fr');
    }

    /** « 16:00:00 » → « 16 h », « 10:30 » → « 10 h 30 ». */
    private function time(?string $time): string
    {
        if ($time === null || $time === '') {
            return '—';
        }
        [$hour, $minute] = array_map('intval', explode(':', $time) + [1 => 0]);

        return $minute === 0 ? "$hour h" : sprintf('%d h %02d', $hour, $minute);
    }
}
