<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderSponsorsRequest extends FormRequest
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
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'distinct', Rule::exists('sponsors', 'id')],
        ];
    }

    /**
     * The dragged ids, in their new order.
     *
     * @return list<int>
     */
    public function order(): array
    {
        return array_values(array_map(intval(...), $this->validated('order')));
    }
}
