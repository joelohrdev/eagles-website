<?php

use App\Enums\UserRole;
use App\Models\User;

test('the database seeder creates the admin user', function () {
    $this->seed();

    $admin = User::query()->where('email', 'jlohr@autorisknow.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->email_verified_at)->not->toBeNull();
});

test('the seeder is idempotent for the admin user', function () {
    $this->seed();
    $this->seed();

    expect(User::query()->where('email', 'jlohr@autorisknow.com')->count())->toBe(1);
});
