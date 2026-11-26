<?php

namespace App\Services\Dashboard;

use App\Enums\ReviewStatus;
use App\Models\IcalFeed;
use App\Repositories\Contracts\ContactMessageRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Compteurs de la barre du back-office (menu et bouton « Alertes ») : conflits, calendriers
 * en échec, messages non lus, avis à modérer et dernière lecture réussie des calendriers. Requêtes légères :
 * la barre les relit toutes les minutes.
 */
class OverviewService
{
    public function __construct(
        private readonly AlertCollector $alerts,
        private readonly ContactMessageRepositoryInterface $messages,
        private readonly ReviewRepositoryInterface $reviews,
    ) {}

    /**
     * @return array{conflicts: int, failing_feeds: int, unread_messages: int, pending_reviews: int, last_sync_at: string|null}
     */
    public function counts(CarbonImmutable $today): array
    {
        $lastSync = $this->alerts->activeFeeds()
            ->map(fn (IcalFeed $feed) => $feed->last_succeeded_at)
            ->filter()
            ->max();

        return [
            'conflicts' => $this->alerts->conflicts($today)->count(),
            'failing_feeds' => $this->alerts->failingFeeds()->count(),
            'unread_messages' => $this->messages->unreadCount(),
            'pending_reviews' => $this->reviews->countByStatus(null)[ReviewStatus::Pending->value] ?? 0,
            'last_sync_at' => $lastSync?->toIso8601String(),
        ];
    }
}
