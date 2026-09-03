<x-mail::message>
# New message from {{ $submission->name }}

**Email:** {{ $submission->email }}
@if ($submission->phone)
**Phone:** {{ $submission->phone }}
@endif
@if ($submission->subject)
**Subject:** {{ $submission->subject }}
@endif

<x-mail::panel>
{{ $submission->message }}
</x-mail::panel>

<x-mail::button :url="$adminUrl">
View in admin
</x-mail::button>

Reply to this email to respond directly to {{ $submission->name }}.

{{ config('app.name') }}
</x-mail::message>
