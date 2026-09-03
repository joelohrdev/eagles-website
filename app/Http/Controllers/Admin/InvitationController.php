<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationRequest;
use App\Mail\StaffInvitation;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $invitation = Invitation::query()->create([
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'token' => Invitation::generateToken(),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(Invitation::EXPIRES_AFTER_DAYS),
        ]);

        $this->send($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent to :email.', ['email' => $invitation->email])]);

        return to_route('admin.users.index');
    }

    public function resend(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_if($invitation->isAccepted(), 404);

        $invitation->forceFill([
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(Invitation::EXPIRES_AFTER_DAYS),
            'invited_by' => $request->user()->id,
        ])->save();

        $this->send($invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation re-sent.')]);

        return to_route('admin.users.index');
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return to_route('admin.users.index');
    }

    private function send(Invitation $invitation): void
    {
        $invitation->load('inviter');

        Mail::to($invitation->email)->queue(
            new StaffInvitation($invitation, route('invitations.accept', $invitation->token)),
        );
    }
}
