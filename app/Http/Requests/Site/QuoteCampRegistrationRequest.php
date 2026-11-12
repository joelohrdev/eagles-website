<?php

namespace App\Http\Requests\Site;

use App\Models\Camp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The days, age group and coupon a parent has picked on the registration form,
 * priced live before they submit. The selection is checked against the camp's
 * schedule by QuoteCampSelection; these rules only cover the shape.
 */
class QuoteCampRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $camp = $this->route('camp');

        return $camp instanceof Camp && $camp->is_published;
    }

    protected function failedAuthorization(): void
    {
        throw new NotFoundHttpException;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::selectionRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::selectionAttributes();
    }

    /**
     * Shared with StoreCampRegistrationRequest.
     *
     * @return array<string, array<int, string>>
     */
    public static function selectionRules(): array
    {
        return [
            'days' => ['required', 'array', 'min:1', 'max:50'],
            'days.*' => ['integer', 'distinct'],
            'camp_age_group_id' => ['nullable', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function selectionAttributes(): array
    {
        return [
            'days' => 'sessions',
            'camp_age_group_id' => 'age group',
            'coupon_code' => 'coupon code',
        ];
    }
}
