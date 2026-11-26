<?php

namespace App\Http\Resources\Admin;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Avis ou invitation dans la file de modération.
 *
 * @mixin Review
 */
class AdminReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'display_name' => $this->display_name,
            'rejection_reason' => $this->rejection_reason,
            'host_reply' => $this->host_reply,
            'apartment' => ['id' => $this->apartment_id, 'name' => $this->apartment->getTranslation('name', 'fr')],
            'booking' => [
                'reference' => $this->booking->reference,
                'guest_name' => $this->booking->guest_name,
                'arrival' => $this->booking->arrival->toDateString(),
                'departure' => $this->booking->departure->toDateString(),
            ],
            'invited_at' => $this->invited_at->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
