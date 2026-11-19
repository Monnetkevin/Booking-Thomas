<x-mail::message :reason="$reason">
# {{ __('mail.booking_cancelled.title') }}

{{ __('mail.booking_cancelled.intro', ['name' => $guestName, 'reference' => $reference]) }}

<x-mail::panel>
**{{ $apartment }}**<br>
{{ $dates }}
</x-mail::panel>

{{ __('mail.booking_cancelled.refund') }}

{{ __('mail.booking_cancelled.questions') }}
</x-mail::message>
