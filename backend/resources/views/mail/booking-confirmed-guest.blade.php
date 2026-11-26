<x-mail::message :reason="$reason">
# {{ __('mail.booking.guest_title') }}

{{ __('mail.booking.guest_intro', ['name' => $guestName]) }}

<x-mail::stay
    :title="$summary['apartment']"
    :subtitle="__('mail.booking.reference', ['reference' => $summary['reference']])"
    :rows="[
        [__('mail.booking.arrival'), $summary['arrival']],
        [__('mail.booking.departure'), $summary['departure']],
        [__('mail.booking.guests'), $summary['guests']],
        [__('mail.booking.address'), $summary['address']],
        ...$summary['lines'],
    ]"
    :total="[__('mail.booking.total_paid'), $summary['total']]"
/>

<x-mail::button :url="$summary['tracking_url']">
{{ __('mail.booking.track') }}
</x-mail::button>

{{ __('mail.booking.pdf_attached') }}

@if ($summary['tiers'] !== [])
**{{ __('mail.booking.cancellation') }}**

@foreach ($summary['tiers'] as $tier)
- {{ $tier }}
@endforeach
@endif

{{ __('mail.booking.questions') }}
</x-mail::message>
