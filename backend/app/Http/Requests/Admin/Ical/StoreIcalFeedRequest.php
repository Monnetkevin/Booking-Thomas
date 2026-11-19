<?php

namespace App\Http\Requests\Admin\Ical;

use App\Enums\IcalProvider;
use Illuminate\Validation\Rule;

class StoreIcalFeedRequest extends IcalFeedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(IcalProvider::class)],
            'import_url' => $this->importUrlRules(),
        ];
    }

    public function provider(): IcalProvider
    {
        return IcalProvider::tryFrom((string) $this->input('provider')) ?? IcalProvider::Airbnb;
    }
}
