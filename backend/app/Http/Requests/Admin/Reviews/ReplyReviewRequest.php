<?php

namespace App\Http\Requests\Admin\Reviews;

use Illuminate\Foundation\Http\FormRequest;

/** Réponse publique de l'hôte ; vide pour l'effacer. */
class ReplyReviewRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['reply' => ['nullable', 'string', 'max:1000']];
    }

    public function reply(): ?string
    {
        $reply = trim($this->string('reply')->toString());

        return $reply === '' ? null : $reply;
    }
}
