<x-mail::message>
# Your refund is on its way, {{ $order->name }}

We've refunded **${{ number_format($order->total / 100, 2) }}** for order **{{ $order->number }}** to your original payment method. It usually shows up within 5–10 business days, depending on your bank.

@if ($order->campRegistration)
**Camp:** {{ $order->campRegistration->camp->name }}<br>
**Player:** {{ $order->campRegistration->playerName() }}<br>
**Date:** {{ $order->campRegistration->camp->starts_at->format('l, F j, Y \a\t g:i A') }}<br>

This registration has been cancelled.
@endif

<x-mail::table>
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->description }}@if ($item->size || $item->color) ({{ collect([$item->size, $item->color])->filter()->implode(' / ') }})@endif | {{ $item->quantity }} | ${{ number_format($item->lineTotal() / 100, 2) }} |
@endforeach
| **Refunded** | | **${{ number_format($order->total / 100, 2) }}** |
</x-mail::table>

Questions? Reply to this email or call us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
