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

test('staff see the unread message count on every admin page', function () {
    ContactSubmission::factory()->count(2)->create();
    ContactSubmission::factory()->read()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('unreadContactCount', 2));
});

test('the unread message count is hidden from guests', function () {
    ContactSubmission::factory()->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('unreadContactCount', 0));
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

test('marking a message unread from its page leaves it unread', function () {
    $submission = ContactSubmission::factory()->read()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->from(route('admin.contact-submissions.show', $submission))
        ->patch(route('admin.contact-submissions.update', $submission), ['read' => 0])
        ->assertRedirect(route('admin.contact-submissions.index'));

    expect($submission->fresh()->read_at)->toBeNull();
});

test('a submission can be deleted', function () {
    $submission = ContactSubmission::factory()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->delete(route('admin.contact-submissions.destroy', $submission))
        ->assertRedirect(route('admin.contact-submissions.index'));

    expect(ContactSubmission::count())->toBe(0);
});
