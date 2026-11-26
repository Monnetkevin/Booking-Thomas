<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'apartment' => ['nullable', 'string', Rule::exists('apartments', 'slug')->whereNull('deleted_at')],
            'captcha_token' => ['nullable', 'string', 'max:4096'],
            // Champ piège, invisible pour un humain.
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }
}
