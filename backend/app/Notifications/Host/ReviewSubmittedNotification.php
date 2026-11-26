<?php

namespace App\Notifications\Host;

use App\Enums\NotificationLevel;
use App\Enums\NotificationType;
use App\Models\Review;
use App\Notifications\BackOfficeNotification;
use Illuminate\Support\Str;

/** Avis déposé par un voyageur, à publier ou refuser. */
class ReviewSubmittedNotification extends BackOfficeNotification
{
    public function __construct(public readonly Review $review)
    {
        parent::__construct();
    }

    public function type(): NotificationType
    {
        return NotificationType::ReviewSubmitted;
    }

    public function level(): NotificationLevel
    {
        return NotificationLevel::Info;
    }

    public function title(): string
    {
        return __('notifications.review_submitted.title', ['apartment' => $this->review->apartment->getTranslation('name', 'fr')], 'fr');
    }

    public function body(): string
    {
        return __('notifications.review_submitted.body', [
            'name' => $this->review->display_name,
            'rating' => $this->review->rating,
            'excerpt' => Str::limit(Str::squish((string) $this->review->comment), 160),
        ], 'fr');
    }

    public function link(): string
    {
        return '/admin/avis';
    }
}
