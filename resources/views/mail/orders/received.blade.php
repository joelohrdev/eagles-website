<x-mail::message>
# New paid {{ $order->type->label() }}

**Order:** {{ $order->number }}
**Customer:** {{ $order->name }} ({{ $order->email }}@if ($order->phone), {{ $order->phone }}@endif)
**Total:** ${{ number_format($order->total / 100, 2) }}

@if ($order->campRegistration)
**Camp:** {{ $order->campRegistration->camp->name }}
**Player:** {{ $order->campRegistration->playerName() }}
@endif

<x-mail::table>
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->description }}@if ($item->size || $item->color) ({{ collect([$item->size, $item->color])->filter()->implode(' / ') }})@endif | {{ $item->quantity }} | ${{ number_format($item->lineTotal() / 100, 2) }} |
@endforeach
</x-mail::table>

<x-mail::button :url="$adminUrl">
View order
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
