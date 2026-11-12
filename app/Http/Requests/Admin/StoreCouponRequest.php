<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * A coupon from the admin form. `value` is a whole percentage for percent
 * coupons and a dollar amount for the fixed types (stored as cents).
 */
class StoreCouponRequest extends FormRequest
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
        $isPercent = $this->input('type') === CouponType::Percent->value;

        return [
            'code' => ['required', 'string', 'min:3', 'max:40', 'alpha_dash:ascii', $this->uniqueCode()],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => $isPercent
                ? ['required', 'integer', 'min:1', 'max:100']
                : ['required', 'numeric', 'min:0.01', 'max:100000'],
            'applies_to' => ['required', Rule::enum(CouponScope::class)],
            'camp_ids' => ['nullable', 'array', 'prohibited_if:applies_to,'.CouponScope::Merch->value],
            'camp_ids.*' => ['integer', 'exists:camps,id'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'max_redemptions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.alpha_dash' => 'Codes can use letters, numbers, dashes and underscores only.',
            'code.unique' => 'Another coupon already uses this code.',
            'value.max' => 'A percentage can’t be more than 100.',
            'expires_at.after' => 'The coupon must expire after it starts.',
            'camp_ids.prohibited_if' => 'Merch-only coupons can’t be limited to camps.',
        ];
    }

    protected function uniqueCode(): Unique
    {
        return Rule::unique('coupons', 'code');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => Coupon::normalizeCode($this->input('code'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function couponAttributes(): array
    {
        $type = CouponType::from($this->validated('type'));

        return [
            ...$this->safe()->only(['code', 'description', 'applies_to', 'starts_at', 'expires_at', 'max_redemptions']),
            'type' => $type,
            'value' => $type === CouponType::Percent
                ? (int) $this->validated('value')
                : (int) round(((float) $this->validated('value')) * 100),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * Camps the coupon is limited to; none means every camp.
     *
     * @return list<int>
     */
    public function campIds(): array
    {
        if ($this->validated('applies_to') === CouponScope::Merch->value) {
            return [];
        }

        return array_map('intval', $this->validated('camp_ids') ?? []);
    }
}
