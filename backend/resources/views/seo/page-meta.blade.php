{{-- Balises d'une page publique, injectées dans index.html. data-server-meta : le site les retire au démarrage, son composant Seo prend le relais. --}}
<title data-server-meta>{{ $meta->title }}</title>
<meta data-server-meta name="description" content="{{ $meta->description }}">
@if ($meta->noIndex)
<meta data-server-meta name="robots" content="noindex">
@endif
<link data-server-meta rel="canonical" href="{{ $urls[$meta->locale] }}">
<link data-server-meta rel="alternate" hreflang="fr" href="{{ $urls['fr'] }}">
<link data-server-meta rel="alternate" hreflang="en" href="{{ $urls['en'] }}">
<link data-server-meta rel="alternate" hreflang="x-default" href="{{ $urls['fr'] }}">
<meta data-server-meta property="og:type" content="website">
<meta data-server-meta property="og:title" content="{{ $meta->title }}">
<meta data-server-meta property="og:description" content="{{ $meta->description }}">
<meta data-server-meta property="og:url" content="{{ $urls[$meta->locale] }}">
<meta data-server-meta property="og:locale" content="{{ $meta->locale === 'fr' ? 'fr_FR' : 'en_GB' }}">
<meta data-server-meta property="og:site_name" content="{{ config('app.name') }}">
@if ($image)
<meta data-server-meta property="og:image" content="{{ $image }}">
@endif
<meta data-server-meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
