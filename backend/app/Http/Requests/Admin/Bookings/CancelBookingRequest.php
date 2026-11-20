<?php

namespace App\Http\Requests\Admin\Bookings;

use Illuminate\Foundation\Http\FormRequest;

/** Annulation par l'hôte : prévenir ou non le voyageur par e-mail. */
class CancelBookingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['notify_guest' => ['required', 'boolean']];
    }

    public function notifyGuest(): bool
    {
        return $this->boolean('notify_guest');
    }
}
