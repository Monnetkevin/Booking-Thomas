<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->state(['status' => BookingStatus::Completed]),
            'apartment_id' => fn (array $attributes) => Booking::query()->findOrFail($attributes['booking_id'])->apartment_id,
            'status' => ReviewStatus::Invited,
            'token_hash' => hash('sha256', fake()->unique()->sha1()),
            'invited_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }

    /** Avis déposé par le voyageur, en attente de modération. */
    public function submitted(int $rating = 5, string $comment = 'Séjour parfait.'): static
    {
        return $this->state(fn () => [
            'status' => ReviewStatus::Pending, 'rating' => $rating, 'comment' => $comment,
            'display_name' => 'Camille', 'submitted_at' => now(),
        ]);
    }

    public function published(int $rating = 5, string $comment = 'Séjour parfait.'): static
    {
        return $this->submitted($rating, $comment)->state(fn () => ['status' => ReviewStatus::Published, 'published_at' => now()]);
    }
}
