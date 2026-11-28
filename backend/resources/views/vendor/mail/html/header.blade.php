@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- Logo en image ; si la messagerie bloque les images, le texte alternatif et le nom restent lisibles. --}}
<img src="{{ \App\Support\SiteUrl::to('/images/logo-email.jpg') }}" class="logo" width="120" height="120" alt="{{ trim(strip_tags($slot)) }}"><br>
<span class="logo-name">{!! $slot !!}</span>
</a>
</td>
</tr>
