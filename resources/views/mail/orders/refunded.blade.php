<x-mail::message>
# Your refund is on its way, {{ $order->name }}

We've refunded **${{ number_format($order->total / 100, 2) }}** for order **{{ $order->number }}** to your original payment method. It usually shows up within 5–10 business days, depending on your bank.

@if ($order->campRegistration)
**Camp:** {{ $order->campRegistration->camp->name }}<br>
**Player:** {{ $order->campRegistration->playerName() }}<br>
@if ($order->campRegistration->ageGroup)
**Age group:** {{ $order->campRegistration->ageGroup->name }}<br>
@endif

**Schedule**<br>
@foreach ($order->campRegistration->scheduleLines() as $line)
{{ $line }}<br>
@endforeach


This registration has been cancelled.
@endif

<x-mail::table>
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->description }}@if ($item->size || $item->color) ({{ collect([$item->size, $item->color])->filter()->implode(' / ') }})@endif | {{ $item->quantity }} | ${{ number_format($item->lineTotal() / 100, 2) }} |
@endforeach
@if ($order->discount > 0)
| Discount ({{ $order->coupon_code }}) | | −${{ number_format($order->discount / 100, 2) }} |
@endif
| **Refunded** | | **${{ number_format($order->total / 100, 2) }}** |
</x-mail::table>

Questions? Reply to this email or call us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
