<?php

use App\Models\CampRegistration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payments\StripeGateway;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\StripeClient;

/**
 * Stand-in for Stripe's HTTP transport that records each request (method,
 * URL, params) and answers with a minimal checkout session, or with the given
 * responses in order, so no request leaves the process.
 *
 * @param  list<array<string, mixed>>  $responses
 */
function recordStripeRequests(array $responses = []): ArrayObject
{
    $requests = new ArrayObject;

    ApiRequestor::setHttpClient(new class($requests, $responses) implements ClientInterface
    {
        /** @param list<array<string, mixed>> $responses */
        public function __construct(private ArrayObject $requests, private array $responses) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = $params + ['_method' => $method, '_url' => $absUrl];
            $body = array_shift($this->responses) ?? ['id' => 'cs_test_123', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.test/cs_test_123'];

            return [json_encode($body), 200, []];
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

test('closing an open checkout page expires it at Stripe', function () {
    $requests = recordStripeRequests([
        ['id' => 'cs_open', 'object' => 'checkout.session', 'status' => 'open'],
        ['id' => 'cs_open', 'object' => 'checkout.session', 'status' => 'expired'],
    ]);
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_open']);

    $closed = (new StripeGateway(new StripeClient('sk_test_fake'), 'whsec_fake'))->closeCheckoutSession($order);

    expect($closed)->toBeTrue()
        ->and($requests)->toHaveCount(2)
        ->and($requests[1]['_method'])->toBe('post')
        ->and($requests[1]['_url'])->toEndWith('/v1/checkout/sessions/cs_open/expire');
});

test('a checkout page that was already paid is not closed', function () {
    recordStripeRequests([['id' => 'cs_paid', 'object' => 'checkout.session', 'status' => 'complete']]);
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_paid']);

    expect((new StripeGateway(new StripeClient('sk_test_fake'), 'whsec_fake'))->closeCheckoutSession($order))->toBeFalse();
});
