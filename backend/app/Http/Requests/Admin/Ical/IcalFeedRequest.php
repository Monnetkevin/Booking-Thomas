<?php

namespace App\Http\Requests\Admin\Ical;

use App\Enums\IcalProvider;
use App\Rules\IcalImportUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Calendrier d'une plateforme : URL d'import facultative (l'export peut servir seul),
 * en HTTPS et sur le domaine de la plateforme.
 */
abstract class IcalFeedRequest extends FormRequest
{
    /** Plateforme du calendrier, qui fixe les domaines acceptés pour l'URL d'import. */
    abstract protected function provider(): IcalProvider;

    protected function prepareForValidation(): void
    {
        if ($this->has('import_url')) {
            $url = trim((string) $this->input('import_url'));
            $this->merge(['import_url' => $url === '' ? null : $url]);
        }
    }

    /**
     * @return list<mixed>
     */
    protected function importUrlRules(): array
    {
        return ['nullable', 'string', 'max:2048', 'url:https', new IcalImportUrl($this->provider())];
    }

    /**
     * @return array<string, mixed>
     */
    public function feedData(): array
    {
        return $this->safe()->only(['import_url', 'is_active']);
    }
}
