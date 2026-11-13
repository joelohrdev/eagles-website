<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Days to take off a camp registration, and the refund in dollars (only needed to confirm).
 */
class DropCampDaysRequest extends FormRequest
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
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer'],
            'refund' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.required' => 'Choose the days this player can’t make.',
        ];
    }

    /**
     * @return list<int>
     */
    public function dayIds(): array
    {
        return array_values(array_map('intval', $this->validated('days')));
    }

    public function refundCents(): int
    {
        return (int) round(((float) ($this->validated('refund') ?? 0)) * 100);
    }
}
