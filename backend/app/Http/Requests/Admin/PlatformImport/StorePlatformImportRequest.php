<?php

namespace App\Http\Requests\Admin\PlatformImport;

use Illuminate\Validation\Rule;

/** Même fichier qu'à l'aperçu, avec le rattachement de chaque annonce à un logement. */
class StorePlatformImportRequest extends PreviewPlatformImportRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'mapping' => ['sometimes', 'array', 'max:100'],
            'mapping.*.listing' => ['required', 'string', 'max:190'],
            'mapping.*.apartment_id' => ['required', 'integer', Rule::exists('apartments', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function mapping(): array
    {
        $map = [];
        foreach ((array) $this->validated('mapping', []) as $entry) {
            $map[(string) $entry['listing']] = (int) $entry['apartment_id'];
        }

        return $map;
    }
}
