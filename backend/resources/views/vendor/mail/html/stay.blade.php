{{--
    Récapitulatif d'un séjour : logement et dates en tête, puis lignes libellé / valeur,
    et un total mis en avant. Utilisation :
    <x-mail::stay :title="$nom" :subtitle="$dates" :rows="[['Voyageurs', '2 adultes']]" :total="['Total', '320,00 €']" />
--}}
@props(['title', 'subtitle' => null, 'rows' => [], 'total' => null])
<table class="stay" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="stay-title" colspan="2">
<strong>{{ $title }}</strong>
@if ($subtitle)
<span>{{ $subtitle }}</span>
@endif
</td>
</tr>
@foreach ($rows as [$label, $value])
<tr class="stay-row">
<td class="stay-label">{{ $label }}</td>
<td class="stay-value" align="right">{{ $value }}</td>
</tr>
@endforeach
@if ($total)
<tr class="stay-total">
<td class="stay-label">{{ $total[0] }}</td>
<td class="stay-value" align="right">{{ $total[1] }}</td>
</tr>
@endif
</table>
