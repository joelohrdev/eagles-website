<x-mail::message>
# Thanks, {{ $order->name }}!

We've received your payment. Here's your receipt for order **{{ $order->number }}**.

@if ($order->campRegistration)
**Camp:** {{ $order->campRegistration->camp->name }}<br>
**Player:** {{ $order->campRegistration->playerName() }}<br>
**Date:** {{ $order->campRegistration->camp->starts_at->format('l, F j, Y \a\t g:i A') }}<br>
@if ($order->campRegistration->camp->location)
**Location:** {{ $order->campRegistration->camp->location }}<br>
@endif
@endif

<x-mail::table>
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->description }}@if ($item->size || $item->color) ({{ collect([$item->size, $item->color])->filter()->implode(' / ') }})@endif | {{ $item->quantity }} | ${{ number_format($item->lineTotal() / 100, 2) }} |
@endforeach
| **Total** | | **${{ number_format($order->total / 100, 2) }}** |
</x-mail::table>

@if ($order->type === \App\Enums\OrderType::Merch)
**Fulfillment:** {{ $order->fulfillment->label() }}<br>
@if ($order->fulfillment === \App\Enums\Fulfillment::Shipping)
{{ $order->shipping_address_line1 }}@if ($order->shipping_address_line2), {{ $order->shipping_address_line2 }}@endif, {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}
@endif
@endif

Questions? Reply to this email or call us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
