<?php

namespace App\Http\Requests\Public;

/** Code promo saisi au paiement, avec le séjour et les options choisies. */
class ValidateCouponRequest extends QuoteRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'code' => ['required', 'string', 'max:40']];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }
}
