<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentSettingsRequest;
use App\Services\Payments\StripeKeyChecker;
use App\Services\StripeSettings;
use App\Support\Payments\WebhookEvent;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingController extends Controller
{
    public function __construct(private StripeSettings $stripe) {}

    public function edit(): Response
    {
        return Inertia::render('admin/payments/Edit', [
            'stripe' => $this->stripe->summary(),
            'webhookUrl' => route('stripe.webhook'),
            'webhookEvents' => [
                WebhookEvent::CHECKOUT_COMPLETED,
                WebhookEvent::CHECKOUT_EXPIRED,
                WebhookEvent::CHARGE_REFUNDED,
            ],
        ]);
    }

    public function update(UpdatePaymentSettingsRequest $request): RedirectResponse
    {
        $this->stripe->save($request->validated('secret_key'), $request->validated('webhook_secret'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stripe keys saved.')]);

        return to_route('admin.payments.edit');
    }

    /**
     * Ask Stripe whether the key in use right now (saved here or from .env) still works.
     */
    public function check(StripeKeyChecker $checker): RedirectResponse
    {
        $key = $this->stripe->secretKey();
        $problem = $key === null ? __('There is no key to check yet. Paste one in Step 3.') : $checker->problemWith($key);

        if ($problem === null) {
            $this->stripe->markKeyChecked();
        }

        Inertia::flash('toast', $problem === null
            ? ['type' => 'success', 'message' => __('Stripe accepted the key.')]
            : ['type' => 'error', 'message' => $problem]);

        return to_route('admin.payments.edit');
    }
}
