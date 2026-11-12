@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- Pastille avec l'initiale du site, en texte : images et SVG sont souvent bloqués par les messageries. --}}
<span class="logo-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(strip_tags($slot)), 0, 1)) }}</span><span class="logo-name">{!! $slot !!}</span>
</a>
</td>
</tr>
