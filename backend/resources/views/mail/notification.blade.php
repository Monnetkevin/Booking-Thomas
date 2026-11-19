<x-mail::message :reason="$reason">
# {{ $title }}

{{ $body }}
@if ($details !== [])

@foreach ($details as $detail)
- {{ $detail }}
@endforeach
@endif

<x-mail::button :url="$adminUrl" color="dark">
{{ __('mail.notification.open_admin') }}
</x-mail::button>
</x-mail::message>
