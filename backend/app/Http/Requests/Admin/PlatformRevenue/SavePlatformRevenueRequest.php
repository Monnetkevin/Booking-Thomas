<?php

namespace App\Http\Requests\Admin\PlatformRevenue;

use App\Enums\IcalProvider;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Montants d'un mois : une entrée par logement et plateforme, net en centimes (null : efface
 * le montant). Pas de mois à venir.
 */
class SavePlatformRevenueRequest extends FormRequest
{
    /** 1 000 000 € : bien au-delà d'un mois de location, attrape une faute de frappe. */
    private const MAX_AMOUNT = 100_000_000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m', 'after_or_equal:2020-01', 'before_or_equal:'.CarbonImmutable::today()->format('Y-m')],
            'entries' => ['present', 'array', 'max:200'],
            'entries.*.apartment_id' => ['required', 'integer', Rule::exists('apartments', 'id')->whereNull('deleted_at')],
            'entries.*.platform' => ['required', Rule::enum(IcalProvider::class)],
            'entries.*.net_amount' => ['present', 'nullable', 'integer', 'min:0', 'max:'.self::MAX_AMOUNT],
        ];
    }

    public function month(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m', $this->string('month')->toString()) ?: CarbonImmutable::today()->startOfMonth();
    }

    /**
     * Une entrée par logement et plateforme : la dernière l'emporte.
     *
     * @return list<array{apartment_id: int, platform: IcalProvider, net_amount: int|null}>
     */
    public function entries(): array
    {
        $entries = [];
        /** @var list<array{apartment_id: int|string, platform: string, net_amount: int|string|null}> $raw */
        $raw = $this->validated('entries');
        foreach ($raw as $entry) {
            $entries[$entry['apartment_id'].'|'.$entry['platform']] = [
                'apartment_id' => (int) $entry['apartment_id'],
                'platform' => IcalProvider::from($entry['platform']),
                'net_amount' => $entry['net_amount'] === null ? null : (int) $entry['net_amount'],
            ];
        }

        return array_values($entries);
    }
}
