<x-mail::message :reason="$reason">
# {{ __('mail.review_invitation.title') }}

{{ __('mail.review_invitation.intro', ['name' => $guestName]) }}

<x-mail::panel>
**{{ $apartment }}**<br>
{{ $dates }}
</x-mail::panel>

{{ __('mail.review_invitation.ask') }}

<x-mail::button :url="$url">
{{ __('mail.review_invitation.button') }}
</x-mail::button>

{{ __('mail.review_invitation.validity', ['date' => $expiresOn]) }}
</x-mail::message>
