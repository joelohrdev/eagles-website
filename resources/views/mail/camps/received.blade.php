<x-mail::message>
# New camp registration

**Camp:** {{ $camp->name }}<br>
@if ($registration->ageGroup)
**Age group:** {{ $registration->ageGroup->name }}<br>
@endif
**Days:** {{ implode('; ', $registration->scheduleLines()) }}<br>
**Player:** {{ $registration->playerName() }} (born {{ $registration->player_birthdate->format('M j, Y') }})<br>
**Parent/Guardian:** {{ $registration->parent_name }}<br>
**Email:** {{ $registration->email }}<br>
**Phone:** {{ $registration->phone }}<br>
@if ($registration->emergency_contact_name)
**Emergency contact:** {{ $registration->emergency_contact_name }} — {{ $registration->emergency_contact_phone }}<br>
@endif
@if ($registration->medical_notes)
**Medical notes:** {{ $registration->medical_notes }}<br>
@endif
**Status:** {{ $registration->status->label() }}<br>

<x-mail::button :url="$adminUrl">
View registrations
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
