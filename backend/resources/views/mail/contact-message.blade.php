<x-mail::message>
# Nouveau message

**De :** {{ $contact->name }} ({{ $contact->email }}{{ $contact->phone ? ', '.$contact->phone : '' }})
@if ($contact->apartment)
**Logement :** {{ $contact->apartment->getTranslation('name', 'fr') }}
@endif

<x-mail::panel>
{{ $contact->message }}
</x-mail::panel>

Répondez directement à cet e-mail pour écrire à {{ $contact->name }}.

<x-mail::button :url="$adminUrl">
Ouvrir le back-office
</x-mail::button>
</x-mail::message>
