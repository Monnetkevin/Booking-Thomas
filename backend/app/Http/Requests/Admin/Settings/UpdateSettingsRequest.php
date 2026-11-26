<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\CancellationPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Réglages de l'hôte. Montants en centimes, pourcentages de 0 à 100 ; un barème
 * d'annulation compte de 1 à 5 paliers aux jours tous différents.
 */
class UpdateSettingsRequest extends FormRequest
{
    public const MAX_TIERS = 5;

    public const MAX_EMAILS = 5;

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('notification_emails'))) {
            $emails = array_map(fn (mixed $email) => mb_strtolower(trim((string) $email)), $this->input('notification_emails'));
            $this->merge(['notification_emails' => array_values(array_unique(array_filter($emails)))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $policies = implode(',', array_column(CancellationPolicy::cases(), 'value'));

        return [
            'min_notice_days' => ['sometimes', 'integer', 'between:0,30'],
            'horizon_months' => ['sometimes', 'integer', 'between:1,24'],
            'tourist_tax' => ['sometimes', 'array:mode,amount,percentage,cap_per_adult_night'],
            'tourist_tax.mode' => ['required_with:tourist_tax', Rule::in(['per_adult_night', 'percentage'])],
            'tourist_tax.amount' => ['required_if:tourist_tax.mode,per_adult_night', 'nullable', 'integer', 'between:0,2000'],
            'tourist_tax.percentage' => ['required_if:tourist_tax.mode,percentage', 'nullable', 'numeric', 'between:0,10'],
            'tourist_tax.cap_per_adult_night' => ['nullable', 'integer', 'between:0,2000'],
            'cancellation_policies' => ['sometimes', 'array:'.$policies],
            'cancellation_policies.*' => ['array', 'min:1', 'max:'.self::MAX_TIERS],
            'cancellation_policies.*.*' => ['array:days_before,refund_percent'],
            'cancellation_policies.*.*.days_before' => ['required', 'integer', 'between:0,365'],
            'cancellation_policies.*.*.refund_percent' => ['required', 'integer', 'between:0,100'],
            'notification_emails' => ['sometimes', 'array', 'min:1', 'max:'.self::MAX_EMAILS],
            'notification_emails.*' => ['email', 'max:190'],
        ];
    }

    /**
     * Les trois barèmes sont envoyés ensemble : un barème manquant serait effacé.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $policies = $this->input('cancellation_policies');
            if (! is_array($policies)) {
                return;
            }
            foreach (CancellationPolicy::cases() as $policy) {
                if (! array_key_exists($policy->value, $policies)) {
                    $validator->errors()->add("cancellation_policies.{$policy->value}", __('validation.required', ['attribute' => $policy->value]));

                    continue;
                }
                // Deux paliers d'un même barème ne tombent jamais le même jour.
                $days = array_column(array_filter((array) $policies[$policy->value], 'is_array'), 'days_before');
                foreach (array_keys($days) as $index) {
                    if (count(array_keys($days, $days[$index])) > 1) {
                        $validator->errors()->add("cancellation_policies.{$policy->value}.$index.days_before", __('validation.distinct', ['attribute' => 'days_before']));
                    }
                }
            }
        }];
    }

    /**
     * Valeurs normalisées : paliers du plus tôt au plus tard, nombres typés.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $data = $this->validated();

        if (isset($data['min_notice_days'])) {
            $data['min_notice_days'] = (int) $data['min_notice_days'];
        }
        if (isset($data['horizon_months'])) {
            $data['horizon_months'] = (int) $data['horizon_months'];
        }
        if (isset($data['tourist_tax'])) {
            $tax = $data['tourist_tax'];
            $byPercentage = $tax['mode'] === 'percentage';
            $data['tourist_tax'] = [
                'mode' => $tax['mode'],
                'amount' => $byPercentage ? 0 : (int) ($tax['amount'] ?? 0),
                'percentage' => $byPercentage ? (float) $tax['percentage'] : null,
                'cap_per_adult_night' => $byPercentage && isset($tax['cap_per_adult_night']) ? (int) $tax['cap_per_adult_night'] : null,
            ];
        }
        if (isset($data['cancellation_policies'])) {
            $data['cancellation_policies'] = array_map(function (array $tiers): array {
                $tiers = array_map(fn (array $tier) => ['days_before' => (int) $tier['days_before'], 'refund_percent' => (int) $tier['refund_percent']], $tiers);
                usort($tiers, fn (array $a, array $b) => $b['days_before'] <=> $a['days_before']);

                return $tiers;
            }, $data['cancellation_policies']);
        }

        return $data;
    }
}
