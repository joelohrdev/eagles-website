<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The coupon editor's unsaved fields, priced for the live preview.
 */
class PreviewCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'numeric', 'min:0', 'max:100000'],
            'combinable_with_multi_session' => ['nullable', 'boolean'],
            'camp_ids' => ['nullable', 'array'],
            'camp_ids.*' => ['integer'],
        ];
    }

    /**
     * An unsaved coupon with the editor's discount, never written to the database.
     */
    public function coupon(): Coupon
    {
        $type = CouponType::from($this->validated('type'));
        $value = (float) $this->validated('value');

        return new Coupon([
            'code' => 'PREVIEW',
            'type' => $type,
            'value' => $type === CouponType::Percent ? (int) min(100, $value) : (int) round($value * 100),
            'combinable_with_multi_session' => $this->boolean('combinable_with_multi_session'),
        ]);
    }

    /**
     * @return list<int>
     */
    public function campIds(): array
    {
        return array_values(array_map('intval', $this->validated('camp_ids') ?? []));
    }
}
