<?php

namespace App\Http\Requests\Admin\Coupons;

use App\Enums\CouponTarget;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Code promo ou offre : code (lettres, chiffres, tirets, enregistré en majuscules), cible,
 * type et valeur, option visée ou offerte, période de validité, limites d'utilisation,
 * conditions de nuits, de montant et de logements, application automatique, activation.
 */
class SaveCouponRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => mb_strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Coupon|null $current */
        $current = $this->route('coupon');
        $type = $this->input('type');
        $target = $this->input('target');
        $needsExtra = $type === CouponType::FreeExtra->value || in_array($target, [CouponTarget::Extra->value, CouponTarget::Both->value], true);

        return [
            'code' => ['required', 'string', 'between:3,40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($current?->id)],
            'target' => ['required', Rule::enum(CouponTarget::class)],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => match ($type) {
                CouponType::Percent->value => ['required', 'integer', 'between:1,100'],
                CouponType::Fixed->value => ['required', 'integer', 'between:1,10000000'],
                default => ['nullable', 'integer'],
            },
            'extra_id' => [$needsExtra ? 'required' : 'nullable', 'integer', Rule::exists('extras', 'id')],
            'free_quantity' => ['nullable', 'integer', 'between:1,99'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'max_uses' => ['nullable', 'integer', 'between:1,100000'],
            'max_uses_per_email' => ['nullable', 'integer', 'between:1,1000'],
            'min_nights' => ['nullable', 'integer', 'between:1,365'],
            'min_amount' => ['nullable', 'integer', 'between:1,10000000'],
            'automatic' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'apartment_ids' => ['present', 'array'],
            'apartment_ids.*' => ['integer', 'distinct', Rule::exists('apartments', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function couponAttributes(): array
    {
        $type = CouponType::from($this->string('type')->toString());
        $target = $type === CouponType::FreeExtra ? CouponTarget::Extra : CouponTarget::from($this->string('target')->toString());
        $nullable = fn (string $field) => $this->filled($field) ? $this->integer($field) : null;

        return [
            'code' => (string) $this->validated('code'),
            'target' => $target,
            'type' => $type,
            'value' => $type === CouponType::FreeExtra ? 0 : $this->integer('value'),
            // L'option ne sert qu'à un code qui la vise ou l'offre.
            'extra_id' => $target === CouponTarget::Stay ? null : $nullable('extra_id'),
            'free_quantity' => $type === CouponType::FreeExtra ? ($nullable('free_quantity') ?? 1) : 1,
            'starts_on' => $this->validated('starts_on'),
            'ends_on' => $this->validated('ends_on'),
            'max_uses' => $nullable('max_uses'),
            'max_uses_per_email' => $nullable('max_uses_per_email'),
            'min_nights' => $nullable('min_nights'),
            'min_amount' => $nullable('min_amount'),
            'automatic' => $this->boolean('automatic'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return list<int>
     */
    public function apartmentIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('apartment_ids')));
    }
}
