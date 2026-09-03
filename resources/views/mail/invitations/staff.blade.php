<x-mail::message>
# You've been invited

@if ($inviterName)
{{ $inviterName }} has invited you to help manage the {{ config('app.name') }} website as **{{ $invitation->role->label() }}**.
@else
You've been invited to help manage the {{ config('app.name') }} website as **{{ $invitation->role->label() }}**.
@endif

Click the button below to create your account. This invitation expires {{ $invitation->expires_at->diffForHumans() }}.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

If you weren't expecting this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
