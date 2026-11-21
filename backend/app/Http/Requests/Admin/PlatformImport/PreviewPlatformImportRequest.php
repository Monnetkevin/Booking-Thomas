<?php

namespace App\Http\Requests\Admin\PlatformImport;

use App\Enums\PayoutPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Fichier CSV exporté d'une plateforme (5 Mo au plus) pour l'aperçu. */
class PreviewPlatformImportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', Rule::enum(PayoutPlatform::class)],
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
        ];
    }

    public function platform(): PayoutPlatform
    {
        return PayoutPlatform::from($this->string('platform')->toString());
    }

    public function contents(): string
    {
        return (string) $this->file('file')?->get();
    }

    public function filename(): string
    {
        return (string) $this->file('file')?->getClientOriginalName();
    }
}
