<?php

namespace App\Http\Requests\Admin\Rate;

use Illuminate\Foundation\Http\FormRequest;

class IndexRatePeriodsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['include_past' => ['sometimes', 'boolean']];
    }
}
