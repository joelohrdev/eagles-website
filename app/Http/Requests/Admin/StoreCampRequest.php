<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampRequest extends FormRequest
{
    use HasSeoRules;

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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'age_range' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            ...$this->seoRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'The end date must be after the start date.',
            'registration_closes_at.after' => 'Registration must close after it opens.',
        ];
    }

    /**
     * Validated camp attributes ready for mass assignment (price converted to cents).
     *
     * @return array<string, mixed>
     */
    public function campAttributes(): array
    {
        $data = $this->safe()->only([
            'name', 'description', 'location', 'age_range', 'starts_at', 'ends_at',
            'capacity', 'registration_opens_at', 'registration_closes_at', 'youtube_url',
        ]);

        $data['price'] = (int) round(((float) $this->validated('price')) * 100);
        $data['is_published'] = $this->boolean('is_published');

        return $data;
    }
}
