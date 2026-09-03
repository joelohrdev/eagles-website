<x-mail::message>
# You're registered, {{ $registration->parent_name }}!

**{{ $registration->playerName() }}** is registered for **{{ $tryout->title }}**.

**When:** {{ $tryout->event_at->format('l, F j, Y \a\t g:i A') }}
@if ($tryout->location)
**Where:** {{ $tryout->location }}
@endif
**Division:** {{ $tryout->division }}

Please arrive 15–20 minutes early to check in. Bring your glove, bat, helmet, cleats, and water.

<x-mail::button :url="$tryoutUrl">
View tryout details
</x-mail::button>

Questions? Just reply to this email.

See you on the field,<br>
{{ config('app.name') }}
</x-mail::message>
