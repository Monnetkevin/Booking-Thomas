<?php

namespace App\Http\Requests\Admin\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use App\Services\Notifications\NotificationPreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Préférences de notification du compte connecté : e-mail par type, récapitulatif. */
class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $types = array_column(NotificationType::for($user->role), 'value');
        [$minHour, $maxHour] = NotificationPreferences::SUMMARY_HOURS;

        return [
            'email' => ['sometimes', 'array:'.implode(',', $types)],
            'email.*' => ['boolean'],
            'summary_enabled' => ['sometimes', 'boolean'],
            'summary_hour' => ['sometimes', 'integer', Rule::in(range($minHour, $maxHour))],
        ];
    }

    /**
     * @return array{email?: array<string, bool>, summary_enabled?: bool, summary_hour?: int}
     */
    public function preferences(): array
    {
        $data = [];
        if ($this->has('email')) {
            $data['email'] = array_map(fn (mixed $wanted) => filter_var($wanted, FILTER_VALIDATE_BOOLEAN), (array) $this->validated('email'));
        }
        if ($this->has('summary_enabled')) {
            $data['summary_enabled'] = $this->boolean('summary_enabled');
        }
        if ($this->has('summary_hour')) {
            $data['summary_hour'] = $this->integer('summary_hour');
        }

        return $data;
    }
}
