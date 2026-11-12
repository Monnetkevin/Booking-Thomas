@props(['title', 'subtitle' => null, 'rows' => [], 'total' => null])
{{ $title }}{{ $subtitle ? ' — '.$subtitle : '' }}
@foreach ($rows as [$label, $value])
- {{ $label }} : {{ $value }}
@endforeach
@if ($total)
{{ $total[0] }} : {{ $total[1] }}
@endif
