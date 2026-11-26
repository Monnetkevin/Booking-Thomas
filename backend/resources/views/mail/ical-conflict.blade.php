<x-mail::message :reason="$reason">
# {{ __('mail.ical_conflict.title') }}

{{ __('mail.ical_conflict.intro', ['provider' => $provider, 'apartment' => $apartment, 'dates' => $dates]) }}

<x-mail::panel>
@foreach ($bookings as $booking)
**{{ $booking['reference'] }}** — {{ $booking['guest'] }} — {{ $booking['dates'] }}<br>
@endforeach
</x-mail::panel>

{{ __('mail.ical_conflict.action', ['provider' => $provider]) }}

@if ($externalUrl)
<x-mail::button :url="$externalUrl">
{{ __('mail.ical_conflict.open_provider', ['provider' => $provider]) }}
</x-mail::button>
@endif

<x-mail::button :url="$adminUrl" color="dark">
{{ __('mail.contact.open_admin') }}
</x-mail::button>
</x-mail::message>
