@props(['reason' => null])
@php($links = \App\Mail\Support\MailLinks::forLocale(app()->getLocale()))
{{ config('app.name') }} — {{ $links['home'] }}

{{ $slot }}
@isset($subcopy)

{{ $subcopy }}
@endisset

--
{{ $reason ?? __('mail.footer.reason', ['site' => config('app.name')]) }}
{{ __('mail.footer.contact') }} : {{ $links['contact'] }}
{{ __('mail.footer.legal') }} : {{ $links['legal'] }}
© {{ date('Y') }} {{ config('app.name') }}
