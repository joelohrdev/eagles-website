<?php

namespace App\Http\Requests\Admin;

class UpdateSponsorRequest extends StoreSponsorRequest
{
    /**
     * The logo is required on create only; an update keeps the stored one when none is sent.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
