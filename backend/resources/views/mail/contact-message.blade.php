<x-mail::message :reason="$reason">
# {{ __('mail.contact.title') }}

**{{ __('mail.contact.from') }}** {{ $contact->name }} ({{ $contact->email }}{{ $contact->phone ? ', '.$contact->phone : '' }})
@if ($contact->apartment)

**{{ __('mail.contact.apartment') }}** {{ $contact->apartment->getTranslation('name', 'fr') }}
@endif

<x-mail::panel>
{{ $contact->message }}
</x-mail::panel>

{{ __('mail.contact.reply', ['name' => $contact->name]) }}

<x-mail::button :url="$adminUrl" color="dark">
{{ __('mail.contact.open_admin') }}
</x-mail::button>
</x-mail::message>
