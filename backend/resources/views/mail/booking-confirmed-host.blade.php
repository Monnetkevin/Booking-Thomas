<x-mail::message :reason="$reason">
# {{ __('mail.booking.host_title') }}

<x-mail::stay
    :title="$summary['apartment']"
    :subtitle="__('mail.booking.reference', ['reference' => $summary['reference']])"
    :rows="[
        [__('mail.booking.arrival'), $summary['arrival']],
        [__('mail.booking.departure'), $summary['departure']],
        [__('mail.booking.guests'), $summary['guests']],
        ...$summary['lines'],
    ]"
    :total="[__('mail.booking.total_paid'), $summary['total']]"
/>

**{{ __('mail.booking.guest') }}** {{ $guest['name'] }} — {{ $guest['email'] }}{{ $guest['phone'] ? ' — '.$guest['phone'] : '' }}{{ $guest['country'] ? ' ('.$guest['country'].')' : '' }}

@if ($guest['message'])
<x-mail::panel>
{{ $guest['message'] }}
</x-mail::panel>
@endif

{{ __('mail.booking.host_reply', ['name' => $guest['name']]) }}

<x-mail::button :url="$adminUrl" color="dark">
{{ __('mail.contact.open_admin') }}
</x-mail::button>
</x-mail::message>
