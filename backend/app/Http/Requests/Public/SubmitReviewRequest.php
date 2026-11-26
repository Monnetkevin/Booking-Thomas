<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/** Avis du voyageur : note de 1 à 5, commentaire, prénom affiché et accord de publication. */
class SubmitReviewRequest extends FormRequest
{
    public const COMMENT_MAX = 1000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:'.self::COMMENT_MAX],
            'display_name' => ['required', 'string', 'max:50'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array{rating: int, comment: string, display_name: string}
     */
    public function review(): array
    {
        return [
            'rating' => $this->integer('rating'),
            'comment' => trim($this->string('comment')->toString()),
            'display_name' => trim($this->string('display_name')->toString()),
        ];
    }
}
