<?php

use App\Enums\UserRole;
use App\Mail\StaffInvitation;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can invite a staff member and an email is queued', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.invitations.store'), ['email' => 'coach@example.com', 'role' => 'staff'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $invitation = Invitation::query()->where('email', 'coach@example.com')->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->role)->toBe(UserRole::Staff)
        ->and($invitation->invited_by)->toBe($admin->id)
        ->and($invitation->isPending())->toBeTrue()
        ->and(strlen($invitation->token))->toBe(64);

    Mail::assertQueued(StaffInvitation::class, function (StaffInvitation $mail) use ($invitation) {
        return $mail->hasTo('coach@example.com')
            && str_contains($mail->acceptUrl, $invitation->token);
    });
});

test('staff cannot send invitations', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->post(route('admin.invitations.store'), ['email' => 'x@example.com', 'role' => 'staff'])
        ->assertForbidden();
});

test('invitation validation rejects existing users, duplicates and bad roles', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'taken@example.com']);
    Invitation::factory()->create(['email' => 'pending@example.com', 'invited_by' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('admin.invitations.store'), ['email' => 'taken@example.com', 'role' => 'staff'])
        ->assertSessionHasErrors(['email']);

    $this->actingAs($admin)
        ->post(route('admin.invitations.store'), ['email' => 'pending@example.com', 'role' => 'staff'])
        ->assertSessionHasErrors(['email']);

    $this->actingAs($admin)
        ->post(route('admin.invitations.store'), ['email' => 'new@example.com', 'role' => 'owner'])
        ->assertSessionHasErrors(['role']);

    Mail::assertNothingQueued();
});

test('an expired invitation can be re-sent with a fresh token', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    $invitation = Invitation::factory()->expired()->create(['invited_by' => $admin->id]);
    $oldToken = $invitation->token;

    $this->actingAs($admin)
        ->post(route('admin.invitations.resend', $invitation))
        ->assertRedirect(route('admin.users.index'));

    $invitation->refresh();

    expect($invitation->token)->not->toBe($oldToken)
        ->and($invitation->isPending())->toBeTrue();

    Mail::assertQueued(StaffInvitation::class);
});

test('admin can revoke an invitation', function () {
    $admin = User::factory()->admin()->create();
    $invitation = Invitation::factory()->create(['invited_by' => $admin->id]);

    $this->actingAs($admin)
        ->delete(route('admin.invitations.destroy', $invitation))
        ->assertRedirect(route('admin.users.index'));

    $this->assertModelMissing($invitation);
});

test('a guest can view a pending invitation', function () {
    $invitation = Invitation::factory()->admin()->create();

    $this->get(route('invitations.accept', $invitation->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/Accept')
            ->where('email', $invitation->email)
            ->where('role', 'Admin')
            ->where('token', $invitation->token)
        );
});

test('expired and accepted invitations show the expired page', function () {
    $expired = Invitation::factory()->expired()->create();
    $accepted = Invitation::factory()->accepted()->create();

    $this->get(route('invitations.accept', $expired->token))
        ->assertStatus(410)
        ->assertInertia(fn (Assert $page) => $page->component('invitations/Expired')->where('accepted', false));

    $this->get(route('invitations.accept', $accepted->token))
        ->assertStatus(410)
        ->assertInertia(fn (Assert $page) => $page->component('invitations/Expired')->where('accepted', true));
});

test('an unknown invitation token is not found', function () {
    $this->get(route('invitations.accept', str_repeat('a', 64)))->assertNotFound();
});

test('accepting an invitation creates a verified user with the invited role and logs in', function () {
    $invitation = Invitation::factory()->create(['email' => 'newstaff@example.com']);

    $this->post(route('invitations.register', $invitation->token), [
        'name' => 'New Staff',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    $user = User::query()->where('email', 'newstaff@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Staff)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($invitation->refresh()->isAccepted())->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('accepting requires a name and confirmed password', function () {
    $invitation = Invitation::factory()->create();

    $this->post(route('invitations.register', $invitation->token), [
        'name' => '',
        'password' => 'password',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['name', 'password']);

    $this->assertGuest();
});

test('an expired invitation cannot be accepted', function () {
    $invitation = Invitation::factory()->expired()->create();

    $this->post(route('invitations.register', $invitation->token), [
        'name' => 'Late',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(410);

    $this->assertGuest();
});

test('authenticated users are redirected away from the invitation page', function () {
    $invitation = Invitation::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('invitations.accept', $invitation->token))
        ->assertRedirect();
});
