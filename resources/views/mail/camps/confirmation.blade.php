<x-mail::message>
# You're registered!

Hi {{ $registration->parent_name }},

**{{ $registration->playerName() }}** is signed up for **{{ $camp->name }}**. Here's everything you signed up for.

@if ($registration->ageGroup)
**Age group:** {{ $registration->ageGroup->name }}
@endif

@foreach ($registration->scheduleBySession() as $session)
## {{ $session['session'] }}

@foreach ($session['days'] as $day)
**{{ $day['title'] }}**@if ($day['time']) — {{ $day['time'] }}@endif<br>
@endforeach

@endforeach
@if ($camp->location || $camp->address)
## Where

@if ($camp->location)
**{{ $camp->location }}**<br>
@endif
@if ($camp->address)
{{ $camp->address }}<br>
[Get directions]({{ $camp->mapUrl() }})
@endif

@endif
@if ($registration->order)
**Order:** {{ $registration->order->number }}<br>
**Paid:** ${{ number_format($registration->order->total / 100, 2) }}@if ($registration->order->discount > 0) (includes {{ $registration->order->coupon_code }} −${{ number_format($registration->order->discount / 100, 2) }})@endif<br>

@endif
<x-mail::button :url="route('camps.show', $camp)">
View camp details
</x-mail::button>

Questions? Reply to this email or call us.

See you on the field,<br>
{{ config('app.name') }}
</x-mail::message>
