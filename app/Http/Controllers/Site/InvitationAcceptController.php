<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\AcceptInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class InvitationAcceptController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = Invitation::query()->where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            return Inertia::render('invitations/Expired', [
                'accepted' => $invitation->isAccepted(),
            ])->toResponse($request)->setStatusCode(410);
        }

        return Inertia::render('invitations/Accept', [
            'token' => $token,
            'email' => $invitation->email,
            'role' => $invitation->role->label(),
        ])->toResponse($request);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = Invitation::query()->where('token', $token)->firstOrFail();

        abort_unless($invitation->isPending(), 410);

        $user = DB::transaction(function () use ($request, $invitation): User {
            $user = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $invitation->email,
                'password' => $request->validated('password'),
                'role' => $invitation->role,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome aboard, :name!', ['name' => $user->name])]);

        return to_route('admin.dashboard');
    }
}
