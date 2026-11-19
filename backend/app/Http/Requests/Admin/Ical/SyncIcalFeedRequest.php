<?php

namespace App\Http\Requests\Admin\Ical;

use Illuminate\Foundation\Http\FormRequest;

/** « Synchroniser maintenant », avec la confirmation éventuelle d'un calendrier vide. */
class SyncIcalFeedRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accept_empty' => ['sometimes', 'boolean'],
        ];
    }

    /** L'hôte confirme que le calendrier est vide chez la plateforme : ses nuits sont libérées. */
    public function acceptsEmpty(): bool
    {
        return $this->boolean('accept_empty');
    }
}
