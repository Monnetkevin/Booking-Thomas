<?php

namespace App\Http\Resources\Public;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Ce que le formulaire d'avis montre au voyageur : son séjour et le prénom proposé,
 * sans autre coordonnée.
 *
 * @mixin Review
 */
class ReviewInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->booking;

        return [
            'apartment' => $this->apartment->getTranslation('name', app()->getLocale()),
            'arrival' => $booking->arrival->toDateString(),
            'departure' => $booking->departure->toDateString(),
            'nights' => $booking->nights(),
            'first_name' => Str::before(trim($booking->guest_name), ' '),
            'expires_on' => $this->expires_at->toDateString(),
        ];
    }
}
