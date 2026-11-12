<x-mail::message>
# New tryout registration

**Tryout:** {{ $tryout->title }} ({{ $tryout->division }})<br>
**Player:** {{ $registration->playerName() }} — born {{ $registration->player_birthdate->format('M j, Y') }}<br>
**Parent/Guardian:** {{ $registration->parent_name }}<br>
**Email:** {{ $registration->email }}<br>
**Phone:** {{ $registration->phone }}<br>
@if ($registration->current_team)
**Current team:** {{ $registration->current_team }}<br>
@endif
@if ($registration->primary_position)
**Position:** {{ $registration->primary_position }}<br>
@endif
@if ($registration->notes)
**Notes:** {{ $registration->notes }}<br>
@endif

<x-mail::button :url="$adminUrl">
View all registrations
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
