<?php

namespace App\Rules;

use App\Enums\IcalProvider;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** URL d'export du calendrier, en HTTPS, sur le domaine de la plateforme. */
class IcalImportUrl implements ValidationRule
{
    public function __construct(private readonly IcalProvider $provider) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = is_string($value) ? parse_url($value, PHP_URL_HOST) : null;

        if (! is_string($host) || ! $this->provider->allowsHost($host)) {
            $fail(__('ical.errors.import_url_host', ['provider' => $this->provider->label()], 'fr'));
        }
    }
}
