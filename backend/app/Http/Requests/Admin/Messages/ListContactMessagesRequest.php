<?php

namespace App\Http\Requests\Admin\Messages;

use Illuminate\Foundation\Http\FormRequest;

/** Messages de contact : tous, ou seulement les non lus. */
class ListContactMessagesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unread' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function unreadOnly(): bool
    {
        return $this->boolean('unread');
    }
}
