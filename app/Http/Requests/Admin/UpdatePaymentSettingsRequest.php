<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Both fields are optional: a blank field keeps the value already saved.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'secret_key' => ['nullable', 'string', 'max:255', 'regex:/^(rk|sk)_(test|live)_[A-Za-z0-9]+$/'],
            'webhook_secret' => ['nullable', 'string', 'max:255', 'regex:/^whsec_[A-Za-z0-9]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'secret_key.regex' => __('That isn\'t a Stripe secret key. Restricted keys start with rk_live_ (or rk_test_ in a sandbox). A key starting with pk_ is the publishable key, which won\'t work here.'),
            'webhook_secret.regex' => __('That isn\'t a webhook signing secret. It starts with whsec_ and is on the webhook destination\'s page in Stripe.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'secret_key' => is_string($this->secret_key) ? trim($this->secret_key) : $this->secret_key,
            'webhook_secret' => is_string($this->webhook_secret) ? trim($this->webhook_secret) : $this->webhook_secret,
        ]);
    }
}
