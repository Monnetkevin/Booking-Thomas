<x-mail::message :reason="$reason">
# {{ __('mail.ical_failing.title') }}

{{ __('mail.ical_failing.intro', ['provider' => $provider, 'apartment' => $apartment, 'failures' => $failures]) }}

<x-mail::panel>
{{ $error }}
</x-mail::panel>

{{ $lastSuccess ? __('mail.ical_failing.last_success', ['date' => $lastSuccess]) : __('mail.ical_failing.never') }}

{{ __('mail.ical_failing.action', ['provider' => $provider]) }}

<x-mail::button :url="$adminUrl" color="dark">
{{ __('mail.ical_failing.open_admin') }}
</x-mail::button>
</x-mail::message>
