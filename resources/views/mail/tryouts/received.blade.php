<x-mail::message>
# New tryout registration

**Tryout:** {{ $tryout->title }} ({{ $tryout->division }})
**Player:** {{ $registration->playerName() }} — born {{ $registration->player_birthdate->format('M j, Y') }}
**Parent/Guardian:** {{ $registration->parent_name }}
**Email:** {{ $registration->email }}
**Phone:** {{ $registration->phone }}
@if ($registration->current_team)
**Current team:** {{ $registration->current_team }}
@endif
@if ($registration->primary_position)
**Position:** {{ $registration->primary_position }}
@endif
@if ($registration->notes)
**Notes:** {{ $registration->notes }}
@endif

<x-mail::button :url="$adminUrl">
View all registrations
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
