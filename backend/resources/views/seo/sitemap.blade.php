{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
@foreach (['fr', 'en'] as $locale)
  <url>
    <loc>{{ $entry[$locale] }}</loc>
    <xhtml:link rel="alternate" hreflang="fr" href="{{ $entry['fr'] }}"/>
    <xhtml:link rel="alternate" hreflang="en" href="{{ $entry['en'] }}"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="{{ $entry['fr'] }}"/>
@if ($entry['lastmod'])
    <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
    <priority>{{ number_format($entry['priority'], 1) }}</priority>
  </url>
@endforeach
@endforeach
</urlset>
