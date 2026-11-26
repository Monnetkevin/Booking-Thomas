{{--
    Confirmation de réservation (PDF, dompdf). Mise en page en tableaux et CSS simple :
    dompdf ne gère ni flex ni grid. DejaVu Sans couvre les accents et le symbole €.
--}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ __('pdf.title') }} {{ $summary['reference'] }}</title>
<style>
    @page { margin: 36px 42px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #2a2119; line-height: 1.5; }
    .muted { color: #6a5b4e; }
    .brand td { vertical-align: middle; }
    .mark { width: 26px; height: 26px; background: #c05630; color: #fff; text-align: center; font-weight: bold; font-size: 14px; border-radius: 6px; }
    .site { font-size: 15px; font-weight: bold; padding-left: 8px; }
    h1 { font-family: 'DejaVu Serif', serif; font-size: 22px; margin: 26px 0 2px; }
    h2 { font-family: 'DejaVu Serif', serif; font-size: 13px; margin: 0 0 8px; }
    .badge { display: inline-block; background: #e3f0e2; color: #2f6b3a; font-weight: bold; padding: 3px 10px; border-radius: 10px; font-size: 10px; }
    .box { border: 1px solid #eadfd3; border-radius: 8px; padding: 14px 16px; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; }
    .rows td { padding: 6px 0; border-bottom: 1px solid #f4ebdd; vertical-align: top; }
    .rows td.label { color: #6a5b4e; width: 38%; }
    .rows td.value { text-align: right; }
    .total td { padding-top: 10px; font-size: 13px; font-weight: bold; }
    .total td.value { color: #b4532f; text-align: right; }
    ul { margin: 4px 0 0; padding-left: 16px; }
    .footer { margin-top: 26px; font-size: 9px; color: #6a5b4e; border-top: 1px solid #eadfd3; padding-top: 10px; }
</style>
</head>
<body>
<table class="brand"><tr>
    <td style="width: 26px"><div class="mark">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 1)) }}</div></td>
    <td class="site">{{ config('app.name') }}</td>
    <td style="text-align: right" class="muted">{{ __('pdf.issued_on', ['date' => $issuedOn]) }}</td>
</tr></table>

<h1>{{ __('pdf.title') }}</h1>
<p class="muted" style="margin: 0 0 8px">{{ __('mail.booking.reference', ['reference' => $summary['reference']]) }}</p>
<span class="badge">{{ __('pdf.status_confirmed') }}</span>

<div class="box">
    <h2>{{ $summary['apartment'] }}</h2>
    <table class="rows">
        <tr><td class="label">{{ __('pdf.guest') }}</td><td class="value">{{ $booking->guest_name }}<br><span class="muted">{{ $booking->guest_email }}</span></td></tr>
        <tr><td class="label">{{ __('mail.booking.address') }}</td><td class="value">{{ $summary['address'] }}</td></tr>
        <tr><td class="label">{{ __('mail.booking.arrival') }}</td><td class="value">{{ $summary['arrival'] }}</td></tr>
        <tr><td class="label">{{ __('mail.booking.departure') }}</td><td class="value">{{ $summary['departure'] }}</td></tr>
        <tr><td class="label">{{ __('mail.booking.guests') }}</td><td class="value">{{ $summary['guests'] }}</td></tr>
    </table>
</div>

<div class="box">
    <h2>{{ __('pdf.price') }}</h2>
    <table class="rows">
        @foreach ($summary['lines'] as [$label, $amount])
            <tr><td class="label">{{ $label }}</td><td class="value">{{ $amount }}</td></tr>
        @endforeach
    </table>
    <table class="total"><tr><td>{{ __('mail.booking.total_paid') }}</td><td class="value">{{ $summary['total'] }}</td></tr></table>
</div>

@if ($summary['tiers'] !== [])
<div class="box">
    <h2>{{ __('mail.booking.cancellation') }}</h2>
    <ul>
        @foreach ($summary['tiers'] as $tier)
            <li>{{ $tier }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="footer">
    {{ __('pdf.footer', ['site' => config('app.name')]) }}
    @if ($registration)
        · {{ __('pdf.registration', ['number' => $registration]) }}
    @endif
    <br>{{ $summary['tracking_url'] }}
</div>
</body>
</html>
