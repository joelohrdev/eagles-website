<x-mail::message>
# Your registration changed

Hi {{ $registration->parent_name }},

We've updated **{{ $registration->playerName() }}**'s registration for **{{ $camp->name }}**. Here's what they're still signed up for.

@foreach ($registration->scheduleBySession() as $session)
## {{ $session['session'] }}

@foreach ($session['days'] as $day)
**{{ $day['title'] }}**@if ($day['time']) — {{ $day['time'] }}@endif<br>
@endforeach

@endforeach
@if ($refund > 0)
**Refund:** ${{ number_format($refund / 100, 2) }} is on its way to the card you paid with. It usually shows up within 5–10 business days.<br>
@endif
@if ($registration->order)
**Order:** {{ $registration->order->number }}<br>
@endif

<x-mail::button :url="route('camps.show', $camp)">
View camp details
</x-mail::button>

Questions? Reply to this email or call us.

See you on the field,<br>
{{ config('app.name') }}
</x-mail::message>
