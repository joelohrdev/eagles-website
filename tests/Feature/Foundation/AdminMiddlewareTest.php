<?php

use App\Models\User;

test('guests are redirected to login from admin-only routes', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->get(route('admin.settings.edit', 'organization'))->assertRedirect(route('login'));
});

test('staff are forbidden from admin-only routes', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.settings.edit', 'organization'))->assertForbidden();
    $this->actingAs($staff)->post(route('admin.invitations.store'), ['email' => 'a@b.co', 'role' => 'staff'])->assertForbidden();
});

test('admins can access admin-only routes', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.settings.edit', 'organization'))->assertOk();
});

test('the shared auth props expose the admin flag', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertInertia(fn ($page) => $page->where('auth.isAdmin', true));

    $this->actingAs($staff)->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.isAdmin', false));
});
