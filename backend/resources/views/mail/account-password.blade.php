<x-mail::message :reason="$reason">
# {{ $invitation ? __('mail.account.invite_title') : __('mail.account.reset_title') }}

{{ $invitation ? __('mail.account.invite_intro', ['name' => $name]) : __('mail.account.reset_intro', ['name' => $name]) }}

<x-mail::button :url="$url" color="dark">
{{ __('mail.account.choose_password') }}
</x-mail::button>

{{ __('mail.account.expires') }}
</x-mail::message>
