{{--
    Gabarit commun des e-mails : en-tête (nom du site), contenu, texte secondaire
    éventuel, pied de page (liens du site et raison de l'envoi). Liens dans la langue
    de l'e-mail.
--}}
@props(['reason' => null])
@php($links = \App\Mail\Support\MailLinks::forLocale(app()->getLocale()))
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="$links['home']">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
{{ $reason ?? __('mail.footer.reason', ['site' => config('app.name')]) }}

[{{ __('mail.footer.site') }}]({{ $links['home'] }}) · [{{ __('mail.footer.contact') }}]({{ $links['contact'] }}) · [{{ __('mail.footer.legal') }}]({{ $links['legal'] }})

© {{ date('Y') }} {{ config('app.name') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
