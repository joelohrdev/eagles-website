<?php

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTryoutRegistrationRequest extends FormRequest
{
    public const array POSITIONS = ['P', 'C', '1B', '2B', 'SS', '3B', 'OF', 'UTIL'];

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
            'player_first_name' => ['required', 'string', 'max:100'],
            'player_last_name' => ['required', 'string', 'max:100'],
            'player_birthdate' => ['required', 'date', 'before:today', 'after:-25 years'],
            'parent_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'current_team' => ['nullable', 'string', 'max:150'],
            'primary_position' => ['nullable', Rule::in(self::POSITIONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'size:0'],
        ];
    }

    /**
     * True when the honeypot field was filled in (bot submission).
     */
    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
