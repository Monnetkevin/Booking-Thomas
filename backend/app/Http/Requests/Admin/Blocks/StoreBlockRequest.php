<?php

namespace App\Http\Requests\Admin\Blocks;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Fermeture d'un logement : nuits [début, fin[, au plus un an, motif facultatif, et si le
 * logement est occupé (ménage à prévoir à la fin).
 */
class StoreBlockRequest extends FormRequest
{
    public const MAX_NIGHTS = 366;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
            'note' => ['nullable', 'string', 'max:120'],
            'needs_cleaning' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->startsOn()->diffInDays($this->endsOn()) > self::MAX_NIGHTS) {
                $validator->errors()->add('ends_on', __('validation.max.numeric', ['attribute' => 'nuits', 'max' => self::MAX_NIGHTS]));
            }
        }];
    }

    public function startsOn(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('starts_on')->toString());
    }

    public function endsOn(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('ends_on')->toString());
    }

    public function note(): ?string
    {
        $note = trim($this->string('note')->toString());

        return $note === '' ? null : $note;
    }

    public function needsCleaning(): bool
    {
        return $this->boolean('needs_cleaning');
    }
}
