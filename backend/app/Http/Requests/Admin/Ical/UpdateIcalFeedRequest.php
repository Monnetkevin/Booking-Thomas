<?php

namespace App\Http\Requests\Admin\Ical;

use App\Enums\IcalProvider;
use App\Models\IcalFeed;

class UpdateIcalFeedRequest extends IcalFeedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_url' => ['sometimes', ...$this->importUrlRules()],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function provider(): IcalProvider
    {
        /** @var IcalFeed $feed */
        $feed = $this->route('icalFeed');

        return $feed->provider;
    }
}
