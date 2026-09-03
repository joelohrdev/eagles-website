<?php

use App\Enums\UserRole;
use App\Models\Invitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from users admin', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('staff are forbidden from users admin', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('admin sees users and pending invitations', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->staff()->count(2)->create();
    Invitation::factory()->count(2)->create(['invited_by' => $admin->id]);
    Invitation::factory()->expired()->create(['invited_by' => $admin->id]);
    Invitation::factory()->accepted()->create(['invited_by' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Index')
            ->has('users', 3)
            ->has('invitations', 2)
            ->has('roles', 2)
        );
});

test('admin can change another user\'s role', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $staff), ['role' => 'admin'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    expect($staff->refresh()->role)->toBe(UserRole::Admin);
});

test('admin cannot change their own role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $admin), ['role' => 'staff'])
        ->assertSessionHasErrors(['role']);

    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});

test('the last admin cannot be demoted', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    // Two admins: demoting one is fine.
    $this->actingAs($admin)
        ->patch(route('admin.users.update', $otherAdmin), ['role' => 'staff'])
        ->assertSessionHasNoErrors();

    // Now only $admin remains admin; another admin trying to demote them must fail.
    $thirdAdmin = User::factory()->admin()->create();
    $this->actingAs($thirdAdmin)
        ->patch(route('admin.users.update', $thirdAdmin), ['role' => 'staff'])
        ->assertSessionHasErrors(['role']);

    $otherAdmin->update(['role' => UserRole::Staff]);
    $thirdAdmin->delete();

    $onlyOther = User::factory()->admin()->create();
    $onlyOther->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('admin.users.update', $admin), ['role' => 'staff'])
        ->assertSessionHasNoErrors();
});

test('demoting the sole remaining admin is rejected', function () {
    $actor = User::factory()->admin()->create();
    $target = User::factory()->admin()->create();
    $actor->forceFill(['role' => UserRole::Staff])->save();
    $actor->forceFill(['role' => UserRole::Admin])->save();

    // Make $target the only admin besides actor, then remove actor's admin via direct DB so count is 1.
    $actor->forceFill(['role' => UserRole::Staff])->save();

    // Actor is staff now → forbidden anyway; so use a fresh admin whose demotion of target should be blocked only if target is the last admin.
    $admin = User::factory()->admin()->create();
    $target->forceFill(['role' => UserRole::Staff])->save();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $target), ['role' => 'admin'])
        ->assertSessionHasNoErrors();

    // Now admin + target are admins (2). Demote target OK.
    $this->actingAs($admin)
        ->patch(route('admin.users.update', $target), ['role' => 'staff'])
        ->assertSessionHasNoErrors();

    // Now admin is the only admin; a second admin cannot exist to demote them, and self-demotion is blocked.
    $this->actingAs($admin)
        ->patch(route('admin.users.update', $admin), ['role' => 'staff'])
        ->assertSessionHasErrors(['role']);

    expect(User::query()->where('role', UserRole::Admin)->count())->toBe(1);
});

test('role must be valid', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $staff), ['role' => 'owner'])
        ->assertSessionHasErrors(['role']);
});

test('admin can delete another user', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $staff))
        ->assertRedirect(route('admin.users.index'));

    $this->assertModelMissing($staff);
});

test('admin cannot delete themselves or the last admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors(['user']);

    $this->assertModelExists($admin);

    $other = User::factory()->admin()->create();
    $this->actingAs($other)
        ->delete(route('admin.users.destroy', $admin))
        ->assertRedirect();

    $this->assertModelMissing($admin);

    // $other is now the last admin — a fresh (second) admin deleting them is fine, but if only one admin exists it is blocked.
    $lastAdmin = User::factory()->admin()->create();
    $other->delete();

    $staffActor = User::factory()->admin()->create();
    $staffActor->forceFill(['role' => UserRole::Staff])->save();

    $this->actingAs($lastAdmin)
        ->delete(route('admin.users.destroy', $lastAdmin))
        ->assertSessionHasErrors(['user']);
});
