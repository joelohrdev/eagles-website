<?php

use App\Models\ContactSubmission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the inbox', function () {
    $this->get(route('admin.contact-submissions.index'))->assertRedirect(route('login'));
});

test('staff can list submissions and filter unread', function () {
    ContactSubmission::factory()->count(2)->create();
    ContactSubmission::factory()->read()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.contact-submissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contact-submissions/Index')
            ->has('submissions.data', 3)
            ->where('unreadCount', 2)
        );

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.contact-submissions.index', ['filter' => 'unread']))
        ->assertInertia(fn (Assert $page) => $page->has('submissions.data', 2)->where('filter', 'unread'));
});

test('viewing a submission marks it read', function () {
    $submission = ContactSubmission::factory()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.contact-submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/contact-submissions/Show')->where('submission.id', $submission->id));

    expect($submission->fresh()->read_at)->not->toBeNull();
});

test('a submission can be toggled unread and read', function () {
    $submission = ContactSubmission::factory()->read()->create();
    $user = User::factory()->staff()->create();

    $this->actingAs($user)->patch(route('admin.contact-submissions.update', $submission), ['read' => 0])->assertRedirect();
    expect($submission->fresh()->read_at)->toBeNull();

    $this->actingAs($user)->patch(route('admin.contact-submissions.update', $submission), ['read' => 1])->assertRedirect();
    expect($submission->fresh()->read_at)->not->toBeNull();
});

test('a submission can be deleted', function () {
    $submission = ContactSubmission::factory()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->delete(route('admin.contact-submissions.destroy', $submission))
        ->assertRedirect(route('admin.contact-submissions.index'));

    expect(ContactSubmission::count())->toBe(0);
});
