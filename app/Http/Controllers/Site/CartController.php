<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreCartItemRequest;
use App\Http\Requests\Site\UpdateCartItemRequest;
use App\Models\ProductVariant;
use App\Services\Cart;
use App\Services\SeoResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private Cart $cart, private SeoResolver $seo) {}

    public function index(): Response
    {
        return Inertia::render('site/Cart/Index', [
            'cart' => $this->cart->toArray(),
            'seo' => $this->seo->forRoute('cart.index', [
                'title' => 'Your Cart',
                'robots' => 'noindex,follow',
            ])->toArray(),
        ]);
    }

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $variant = $request->variant();

        $this->cart->add($variant, $request->quantity());

        $total = $this->cart->items()[$variant->id] ?? $request->quantity();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $total > $request->quantity()
                ? __('Added :qty × :item — you now have :total in your cart.', ['qty' => $request->quantity(), 'item' => $variant->product->name, 'total' => $total])
                : __(':item added to your cart.', ['item' => $variant->product->name]),
        ]);

        return to_route('cart.index');
    }

    public function update(UpdateCartItemRequest $request, ProductVariant $variant): RedirectResponse
    {
        $this->cart->update($variant, $request->integer('quantity'));

        return back();
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        $this->cart->remove($variant);

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Item removed from your cart.')]);

        return back();
    }
}
