<?php

use App\Models\Camp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('staff can set a 4-digit preview passcode, stored encrypted', function () {
    $staff = User::factory()->staff()->create();
    $camp = Camp::factory()->unpublished()->create();

    $this->actingAs($staff)->put(route('admin.camps.update', $camp), [
        'name' => $camp->name,
        'preview_passcode' => '0427',
    ])->assertSessionHasNoErrors();

    expect($camp->refresh()->preview_passcode)->toBe('0427')
        ->and(DB::table('camps')->where('id', $camp->id)->value('preview_passcode'))->not->toBe('0427');

    $this->actingAs($staff)->get(route('admin.camps.edit', $camp))
        ->assertInertia(fn ($page) => $page
            ->where('camp.preview_passcode', '0427')
            ->where('camp.preview_url', route('camps.preview', $camp)));

    $this->actingAs($staff)->put(route('admin.camps.update', $camp), ['name' => $camp->name, 'preview_passcode' => '12a'])
        ->assertSessionHasErrors(['preview_passcode' => 'The passcode must be exactly 4 digits.']);
});

test('without a passcode, visitors are sent to log in and come back to the preview', function () {
    $camp = Camp::factory()->unpublished()->create();

    $this->get(route('camps.preview', $camp))->assertRedirect(route('login'));

    expect(session('url.intended'))->toBe(route('camps.preview', $camp));
});

test('with a passcode, visitors see the passcode screen and can still choose to log in', function () {
    $camp = Camp::factory()->unpublished()->create(['preview_passcode' => '0427']);

    $this->get(route('camps.preview.register', $camp))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Camps/PreviewLocked')
            ->where('to', 'camps.preview.register')
            ->where('seo.robots', 'noindex,nofollow'));

    $this->get(route('camps.preview', ['camp' => $camp, 'login' => 1]))->assertRedirect(route('login'));
});

test('the right passcode opens the preview pages for that browser, without edit links', function () {
    $camp = Camp::factory()->unpublished()->create(['preview_passcode' => '0427']);

    $this->from(route('camps.preview', $camp))
        ->post(route('camps.preview.unlock', $camp), ['passcode' => '1111'])
        ->assertRedirect(route('camps.preview', $camp))
        ->assertSessionHasErrors(['passcode' => 'That passcode isn’t right.']);

    $this->post(route('camps.preview.unlock', $camp), ['passcode' => '0427', 'to' => 'camps.preview.register'])
        ->assertRedirect(route('camps.preview.register', $camp));

    $this->get(route('camps.preview', $camp))
        ->assertInertia(fn ($page) => $page
            ->component('site/Camps/Show')
            ->where('preview.is_draft', true)
            ->where('preview.edit_url', null));
    $this->postJson(route('camps.preview.quote', $camp), ['days' => $camp->days()->pluck('camp_session_days.id')->all()])->assertOk();

    $other = Camp::factory()->unpublished()->create(['preview_passcode' => '0427']);
    $this->get(route('camps.preview', $other))->assertInertia(fn ($page) => $page->component('site/Camps/PreviewLocked'));
});

test('changing or clearing the passcode locks out anyone who used the old one', function () {
    $camp = Camp::factory()->unpublished()->create(['preview_passcode' => '0427']);
    $this->post(route('camps.preview.unlock', $camp), ['passcode' => '0427']);

    $camp->update(['preview_passcode' => '9999']);
    $this->get(route('camps.preview', $camp))->assertInertia(fn ($page) => $page->component('site/Camps/PreviewLocked'));

    $camp->update(['preview_passcode' => null]);
    $this->get(route('camps.preview', $camp))->assertRedirect(route('login'));
});

test('passcode guesses are limited to five a minute', function () {
    $camp = Camp::factory()->unpublished()->create(['preview_passcode' => '0427']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('camps.preview.unlock', $camp), ['passcode' => '0000'])->assertRedirect();
    }

    $this->post(route('camps.preview.unlock', $camp), ['passcode' => '0427'])->assertTooManyRequests();
});

test('signed-in staff skip the passcode and get an edit link', function () {
    $camp = Camp::factory()->unpublished()->create(['preview_passcode' => '0427']);

    $this->actingAs(User::factory()->staff()->create())->get(route('camps.preview', $camp))
        ->assertInertia(fn ($page) => $page
            ->component('site/Camps/Show')
            ->where('preview.edit_url', route('admin.camps.edit', $camp)));
});

test('an archived camp’s passcode no longer opens its preview', function () {
    $camp = Camp::factory()->unpublished()->create(['preview_passcode' => '0427']);
    $this->post(route('camps.preview.unlock', $camp), ['passcode' => '0427']);

    $camp->forceFill(['archived_at' => now()])->save();

    $this->get(route('camps.preview', $camp))->assertRedirect(route('login'));
});
