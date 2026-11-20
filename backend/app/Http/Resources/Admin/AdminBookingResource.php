<?php

namespace App\Http\Resources\Admin;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\Booking\RefundProposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fiche d'une réservation pour l'hôte : voyageur, séjour, détail du prix, paiements,
 * conditions d'annulation et remboursement proposé si on l'annule aujourd'hui.
 *
 * @mixin Booking
 */
class AdminBookingResource extends JsonResource
{
    /**
     * @param  list<array<string, mixed>>  $history
     */
    public function __construct(Booking $booking, private readonly ?RefundProposal $refund = null, private readonly array $history = [])
    {
        parent::__construct($booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new AdminBookingListResource($this->resource))->toArray($request),
            'guest_email' => $this->guest_email,
            'guest_phone' => $this->guest_phone,
            'guest_country' => $this->guest_country,
            'guest_message' => $this->guest_message,
            'locale' => $this->locale,
            'adults' => $this->adults,
            'children' => $this->children,
            'housekeeping_notes' => $this->housekeeping_notes,
            'accommodation_amount' => $this->accommodation_amount,
            'cleaning_fee' => $this->cleaning_fee,
            'extras_amount' => $this->extras_amount,
            'tourist_tax' => $this->tourist_tax,
            'discount_amount' => $this->discount_amount,
            'price_lines' => $this->price_breakdown['lines'] ?? [],
            'cancellation_policy' => $this->cancellation_policy,
            'payments' => $this->payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'type' => $payment->type->value,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at?->toIso8601String(),
            ])->values()->all(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'refund_proposal' => $this->refund?->toArray(),
            'history' => $this->history,
        ];
    }
}
