<x-mail::message>
# New camp registration

**Camp:** {{ $camp->name }} ({{ $camp->starts_at->format('M j, Y g:i A') }})
**Player:** {{ $registration->playerName() }} (born {{ $registration->player_birthdate->format('M j, Y') }})
**Parent/Guardian:** {{ $registration->parent_name }}
**Email:** {{ $registration->email }}
**Phone:** {{ $registration->phone }}
@if ($registration->emergency_contact_name)
**Emergency contact:** {{ $registration->emergency_contact_name }} — {{ $registration->emergency_contact_phone }}
@endif
@if ($registration->medical_notes)
**Medical notes:** {{ $registration->medical_notes }}
@endif
**Status:** {{ $registration->status->label() }}

<x-mail::button :url="$adminUrl">
View registrations
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
