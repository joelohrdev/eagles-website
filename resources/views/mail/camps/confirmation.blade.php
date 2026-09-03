<x-mail::message>
# You're registered!

Hi {{ $registration->parent_name }},

**{{ $registration->playerName() }}** is registered for **{{ $camp->name }}**.

**When:** {{ $camp->starts_at->format('l, F j, Y \a\t g:i A') }}@if ($camp->ends_at) – {{ $camp->ends_at->isSameDay($camp->starts_at) ? $camp->ends_at->format('g:i A') : $camp->ends_at->format('l, F j, Y \a\t g:i A') }}@endif
@if ($camp->location)
**Where:** {{ $camp->location }}
@endif
@if ($camp->age_range)
**Ages:** {{ $camp->age_range }}
@endif

<x-mail::button :url="route('camps.show', $camp)">
View camp details
</x-mail::button>

Questions? Reply to this email or call us.

See you on the field,<br>
{{ config('app.name') }}
</x-mail::message>
