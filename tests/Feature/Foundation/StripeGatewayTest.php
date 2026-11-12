<?php

use App\Models\CampRegistration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payments\StripeGateway;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\StripeClient;

/**
 * Stand-in for Stripe's HTTP transport that records the params of each request
 * and answers with a minimal checkout session, so no request leaves the process.
 */
function recordStripeRequests(): ArrayObject
{
    $requests = new ArrayObject;

    ApiRequestor::setHttpClient(new class($requests) implements ClientInterface
    {
        public function __construct(private ArrayObject $requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = $params;

            return [json_encode(['id' => 'cs_test_123', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.test/cs_test_123']), 200, []];
        }
    });

    return $requests;
}

afterEach(function () {
    ApiRequestor::setHttpClient(null);
});

test('a camp checkout session closes when the registration hold expires, past the Stripe 30-minute minimum', function () {
    $this->freezeTime();
    $requests = recordStripeRequests();
    $order = Order::factory()->camp()->has(OrderItem::factory(), 'items')->create();
    $registration = CampRegistration::factory()->pending()->for($order)->create([
        'expires_at' => now()->addMinutes(CampRegistration::PENDING_HOLD_MINUTES),
    ]);

    $session = (new StripeGateway(new StripeClient('sk_test_fake'), 'whsec_fake'))
        ->createCheckoutSession($order, 'https://example.test/success', 'https://example.test/cancel');

    expect($session->id)->toBe('cs_test_123')
        ->and($requests)->toHaveCount(1)
        ->and($requests[0]['expires_at'])->toBe($registration->expires_at->getTimestamp())
        ->and($requests[0]['expires_at'])->toBeGreaterThan(now()->addMinutes(30)->getTimestamp());
});

test('a merch checkout session keeps the default Stripe lifetime', function () {
    $requests = recordStripeRequests();
    $order = Order::factory()->has(OrderItem::factory(), 'items')->create();

    (new StripeGateway(new StripeClient('sk_test_fake'), 'whsec_fake'))
        ->createCheckoutSession($order, 'https://example.test/success', 'https://example.test/cancel');

    expect($requests[0])->not->toHaveKey('expires_at');
});

test('a discounted order sends its discount to Stripe as a single-use coupon', function () {
    $requests = recordStripeRequests();
    $order = Order::factory()->has(OrderItem::factory(), 'items')->create(['subtotal' => 25000, 'discount' => 5000, 'total' => 20000, 'coupon_code' => 'THANKS30']);

    (new StripeGateway(new StripeClient('sk_test_fake'), 'whsec_fake'))
        ->createCheckoutSession($order, 'https://example.test/success', 'https://example.test/cancel');

    expect($requests)->toHaveCount(2)
        ->and($requests[0])->toMatchArray(['amount_off' => 5000, 'currency' => 'usd', 'duration' => 'once', 'max_redemptions' => 1, 'name' => 'THANKS30'])
        ->and($requests[1]['discounts'])->toBe([['coupon' => 'cs_test_123']]);
});
