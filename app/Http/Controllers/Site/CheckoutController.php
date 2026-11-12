<?php

namespace App\Http\Controllers\Site;

use App\Actions\Orders\CancelPendingOrder;
use App\Actions\Orders\CreateCheckoutSession;
use App\Actions\Orders\CreateMerchOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Contracts\PaymentGateway;
use App\Enums\Fulfillment;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\QuoteCheckoutRequest;
use App\Http\Requests\Site\StoreCheckoutRequest;
use App\Models\Order;
use App\Services\Cart;
use App\Services\SeoResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private Cart $cart,
        private SeoResolver $seo,
    ) {}

    /**
     * Contact + fulfillment details before handing off to Stripe.
     */
    public function create(): Response|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return to_route('cart.index');
        }

        return Inertia::render('site/Checkout/Create', [
            'cart' => $this->cart->toArray(),
            'shippingCents' => CreateMerchOrder::SHIPPING_FLAT_CENTS,
            'fulfillmentOptions' => collect(Fulfillment::cases())
                ->map(fn (Fulfillment $f) => ['value' => $f->value, 'label' => $f->label()])
                ->all(),
            'seo' => $this->seo->forRoute('checkout.create', [
                'title' => 'Checkout',
                'robots' => 'noindex,nofollow',
            ])->toArray(),
        ]);
    }

    /**
     * Price the cart with a coupon for the checkout page. Placing the order prices it again.
     */
    public function quote(QuoteCheckoutRequest $request, CreateMerchOrder $createOrder): JsonResponse
    {
        if ($this->cart->isEmpty()) {
            return response()->json(['message' => __('Your cart is empty.')], 422);
        }

        return response()->json($createOrder->quote(
            $request->validated('coupon_code'),
            Fulfillment::from($request->validated('fulfillment') ?? Fulfillment::Pickup->value),
        ));
    }

    /**
     * Create the pending order and redirect to the hosted payment page.
     */
    public function store(
        StoreCheckoutRequest $request,
        CreateMerchOrder $createOrder,
        CreateCheckoutSession $createSession,
        MarkOrderPaid $markOrderPaid,
    ): SymfonyResponse|RedirectResponse {
        if ($request->isSpam()) {
            return to_route('cart.index');
        }

        $order = $createOrder->handle($request->validated());
        $successUrl = URL::signedRoute('checkout.success', ['order' => $order->number]);

        if ($order->total === 0) {
            /** A coupon covered everything: there is nothing to pay, so confirm it here. */
            $markOrderPaid->handle($order);

            return redirect()->to($successUrl);
        }

        $session = $createSession->handle(
            $order,
            $successUrl,
            URL::signedRoute('checkout.cancel', ['order' => $order->number]),
        );

        return Inertia::location($session->url);
    }

    /**
     * Post-payment landing page for both merch and camp orders (signed URL).
     */
    public function success(Order $order): Response
    {
        $order->load(['items', 'campRegistration.camp']);

        if ($order->isPaid() && $order->type === OrderType::Merch) {
            $this->cart->clear();
        }

        return Inertia::render('site/Checkout/Success', [
            'order' => [
                'number' => $order->number,
                'type' => $order->type->value,
                'status' => $order->status->value,
                'is_paid' => $order->isPaid(),
                'is_pending' => $order->isPending(),
                'name' => $order->name,
                'email' => $order->email,
                'fulfillment' => $order->fulfillment->value,
                'fulfillment_label' => $order->fulfillment->label(),
                'shipping' => $order->fulfillment === Fulfillment::Shipping ? [
                    'line1' => $order->shipping_address_line1,
                    'line2' => $order->shipping_address_line2,
                    'city' => $order->shipping_city,
                    'state' => $order->shipping_state,
                    'postal_code' => $order->shipping_postal_code,
                ] : null,
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'discount_label' => $order->coupon_code,
                'total' => $order->total,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'size' => $item->size,
                    'color' => $item->color,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->lineTotal(),
                ])->values(),
                'camp_registration' => $order->campRegistration ? [
                    'player_name' => $order->campRegistration->playerName(),
                    'age_group' => $order->campRegistration->ageGroup?->name,
                    'schedule' => $order->campRegistration->scheduleLines(),
                    'status' => $order->campRegistration->status->value,
                    'camp' => [
                        'name' => $order->campRegistration->camp->name,
                        'slug' => $order->campRegistration->camp->slug,
                        'starts_at' => $order->campRegistration->camp->starts_at,
                        'ends_at' => $order->campRegistration->camp->ends_at,
                        'location' => $order->campRegistration->camp->location,
                        'url' => route('camps.show', $order->campRegistration->camp),
                    ],
                ] : null,
            ],
            'seo' => $this->seo->forRoute('checkout.success', [
                'title' => $order->isPaid() ? 'Order confirmed' : 'Confirming payment',
                'robots' => 'noindex,nofollow',
            ])->toArray(),
        ]);
    }

    /**
     * Customer backed out of Stripe: release the pending order (signed URL).
     */
    public function cancel(Order $order, CancelPendingOrder $cancelPendingOrder, PaymentGateway $gateway): RedirectResponse
    {
        $order->load('campRegistration.camp');

        if ($order->isPending()) {
            /**
             * Close Stripe's payment page before releasing anything. Otherwise
             * the customer could still pay it after the spot and coupon use
             * were given back (overbooking a camp, reusing a one-use coupon).
             */
            if (! $gateway->closeCheckoutSession($order)) {
                Inertia::flash('toast', ['type' => 'info', 'message' => __('Your payment is still being confirmed.')]);

                return redirect()->to(URL::signedRoute('checkout.success', ['order' => $order->number]));
            }

            $cancelPendingOrder->handle($order);
        }

        if ($order->type === OrderType::Camp && $order->campRegistration?->camp) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Payment cancelled — your spot was released.')]);

            return to_route('camps.show', $order->campRegistration->camp);
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Payment cancelled. Your cart is still here when you are ready.')]);

        return to_route('cart.index');
    }
}
