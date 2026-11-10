<?php

namespace App\Http\Requests\Admin\Photo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UploadPhotosRequest extends FormRequest
{
    /**
     * Type MIME réel du fichier (pas l'extension), 20 Mo au plus, 800 × 600 px au moins.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => [
                File::types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(20 * 1024)
                    ->image()
                    ->dimensions(Rule::dimensions()->minWidth(800)->minHeight(600)),
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function photos(): array
    {
        /** @var list<UploadedFile> */
        return array_values($this->file('photos', []));
    }
}
