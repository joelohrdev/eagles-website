<?php

namespace App\Http\Requests\Site;

use App\Models\Camp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreCampRegistrationRequest extends FormRequest
{
    /**
     * Unpublished camps are not registerable — treat them as not found.
     */
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
        return [
            'player_first_name' => ['required', 'string', 'max:100'],
            'player_last_name' => ['required', 'string', 'max:100'],
            'player_birthdate' => ['required', 'date', 'before:today', 'after:-25 years'],
            'parent_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'player_first_name' => 'player first name',
            'player_last_name' => 'player last name',
            'player_birthdate' => 'player birthdate',
            'parent_name' => 'parent/guardian name',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
        ];
    }

    /**
     * Whether the honeypot field was filled (bot submission).
     */
    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
