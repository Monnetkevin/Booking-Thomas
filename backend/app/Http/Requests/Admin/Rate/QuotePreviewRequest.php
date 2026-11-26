<?php

namespace App\Http\Requests\Admin\Rate;

use App\Http\Requests\Public\QuoteRequest;
use App\Services\Booking\Stay;
use Illuminate\Foundation\Http\FormRequest;

class QuotePreviewRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return QuoteRequest::stayRules();
    }

    public function stay(): Stay
    {
        return Stay::fromStrings(
            $this->string('arrival')->toString(),
            $this->string('departure')->toString(),
            $this->integer('adults'),
            $this->integer('children'),
        );
    }
}
