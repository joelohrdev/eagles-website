<?php

use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampRegistration;
use App\Models\CampSessionDay;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

/**
 * The Thanksgiving camp as the admin form submits it.
 *
 * @return array<string, mixed>
 */
function thanksgivingCampForm(array $overrides = []): array
{
    $times = fn (string $young, string $youngEnd, string $older, string $olderEnd): array => [
        ['age_group_key' => 'young', 'starts_at' => $young, 'ends_at' => $youngEnd],
        ['age_group_key' => 'older', 'starts_at' => $older, 'ends_at' => $olderEnd],
    ];

    return array_replace([
        'name' => 'Thanksgiving Camp',
        'bundle_price' => '225',
        'is_published' => 1,
        'age_groups' => [
            ['key' => 'young', 'name' => '8–12U'],
            ['key' => 'older', 'name' => '13–17U', 'capacity' => 12],
        ],
        'sessions' => [
            [
                'name' => 'Pitching',
                'price' => '125',
                'day_price' => '75',
                'days' => [
                    ['date' => '2026-11-21', 'times' => $times('09:00', '10:30', '10:30', '12:00')],
                    ['date' => '2026-11-23', 'times' => $times('17:30', '19:00', '19:00', '20:30')],
                ],
            ],
            [
                'name' => 'Fielding & Hitting',
                'price' => '125',
                'days' => [
                    ['date' => '2026-11-24', 'label' => 'Fielding', 'times' => $times('17:30', '19:00', '19:00', '20:30')],
                    ['date' => '2026-11-25', 'label' => 'Hitting', 'times' => $times('17:30', '19:00', '19:00', '20:30')],
                ],
            ],
        ],
    ], $overrides);
}

test('staff can build a multi-session camp with age group times', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), thanksgivingCampForm())->assertSessionHasNoErrors();

    $camp = Camp::query()->sole()->loadSchedule();
    $older = $camp->ageGroups->firstWhere('name', '13–17U');
    $saturday = $camp->sessions->first()->days->first();

    expect($camp->sessions->pluck('name')->all())->toBe(['Pitching', 'Fielding & Hitting'])
        ->and($camp->sessions->pluck('price')->all())->toBe([12500, 12500])
        ->and($camp->sessions->first()->day_price)->toBe(7500)
        ->and($camp->sessions->last()->day_price)->toBeNull()
        ->and($camp->bundle_price)->toBe(22500)
        ->and($older->capacity)->toBe(12)
        ->and($saturday->timeFor($older->id)->range())->toBe('10:30 AM – 12:00 PM')
        ->and($camp->sessions->last()->days->pluck('label')->all())->toBe(['Fielding', 'Hitting'])
        ->and($camp->starts_at->format('Y-m-d H:i'))->toBe('2026-11-21 09:00')
        ->and($camp->ends_at->format('Y-m-d H:i'))->toBe('2026-11-25 20:30')
        ->and($camp->price)->toBe(7500);
});

test('the edit page round-trips the schedule and saving it keeps existing rows', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), thanksgivingCampForm());
    $camp = Camp::query()->sole();
    $dayIds = $camp->days()->pluck('camp_session_days.id')->all();

    $schedule = $this->actingAs($this->staff)->get(route('admin.camps.edit', $camp))
        ->assertOk()
        ->viewData('page')['props']['camp']['schedule'];

    $this->actingAs($this->staff)
        ->put(route('admin.camps.update', $camp), thanksgivingCampForm($schedule))
        ->assertSessionHasNoErrors();

    expect($camp->days()->pluck('camp_session_days.id')->all())->toBe($dayIds)
        ->and($camp->ageGroups()->count())->toBe(2);
});

test('a day with booked players cannot be removed, but an empty one can', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), thanksgivingCampForm());
    $camp = Camp::query()->sole();
    $schedule = $this->actingAs($this->staff)->get(route('admin.camps.edit', $camp))->viewData('page')['props']['camp']['schedule'];
    $hittingDay = CampSessionDay::query()->where('label', 'Hitting')->sole();

    $withoutHitting = $schedule;
    array_pop($withoutHitting['sessions'][1]['days']);

    CampRegistration::factory()->for($camp)->forDays([$hittingDay])->create();

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), thanksgivingCampForm($withoutHitting))
        ->assertSessionHasErrors('sessions');
    $this->assertModelExists($hittingDay);

    $camp->registrations()->delete();

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), thanksgivingCampForm($withoutHitting))
        ->assertSessionHasNoErrors();
    $this->assertModelMissing($hittingDay);
});

test('every day needs a time for each age group, ending after it starts', function () {
    $form = thanksgivingCampForm();
    array_pop($form['sessions'][0]['days'][0]['times']);
    $form['sessions'][1]['days'][0]['times'][0]['ends_at'] = '17:00';

    $this->actingAs($this->staff)->post(route('admin.camps.store'), $form)
        ->assertSessionHasErrors(['sessions.0.days.0.times', 'sessions.1.days.0.times.0.ends_at']);

    expect(Camp::count())->toBe(0);
});

test('a draft can be saved with only a name and listed as a draft', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), ['name' => 'Spring Camp', 'is_published' => 0])
        ->assertSessionHasNoErrors();

    $camp = Camp::query()->sole();

    expect($camp->is_published)->toBeFalse()
        ->and($camp->starts_at)->toBeNull()
        ->and($camp->sessions()->count())->toBe(0);

    $this->actingAs($this->staff)->get(route('admin.camps.index'))
        ->assertInertia(fn ($page) => $page->where('camps.data.0.registration_state', 'draft')->where('camps.data.0.starts_at', null));
    $this->actingAs($this->staff)->get(route('admin.camps.edit', $camp))->assertOk();
    $this->get(route('camps.show', $camp))->assertNotFound();
});

test('a draft keeps half-filled sessions, days and times', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), [
        'name' => 'Spring Camp',
        'is_published' => 0,
        'sessions' => [[
            'name' => 'Pitching',
            'days' => [
                ['label' => 'Bullpens', 'times' => [['starts_at' => '17:30']]],
                ['date' => '2027-03-14', 'times' => [['starts_at' => '', 'ends_at' => '']]],
            ],
        ]],
    ])->assertSessionHasNoErrors();

    $camp = Camp::query()->sole();
    $schedule = $this->actingAs($this->staff)->get(route('admin.camps.edit', $camp))->viewData('page')['props']['camp']['schedule'];

    expect($schedule['sessions'][0]['price'])->toBeNull()
        ->and($schedule['sessions'][0]['days'][0]['date'])->toBeNull()
        ->and($schedule['sessions'][0]['days'][0]['label'])->toBe('Bullpens')
        ->and($schedule['sessions'][0]['days'][0]['times'][0]['starts_at'])->toBe('17:30')
        ->and($schedule['sessions'][0]['days'][1]['date'])->toBe('2027-03-14')
        ->and($schedule['sessions'][0]['days'][1]['times'])->toBe([])
        ->and($camp->starts_at)->toBeNull();
});

test('publishing needs prices, dates and a time for every day', function () {
    $camp = Camp::factory()->unpublished()->withoutSchedule()->create();

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), [
        'name' => $camp->name,
        'is_published' => 1,
        'sessions' => [[
            'name' => 'Pitching',
            'days' => [['times' => [['ends_at' => '19:00']]]],
        ]],
    ])->assertSessionHasErrors(['sessions.0.price', 'sessions.0.days.0.date', 'sessions.0.days.0.times.0.starts_at']);

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), ['name' => $camp->name, 'is_published' => 1])
        ->assertSessionHasErrors('sessions');

    expect($camp->refresh()->is_published)->toBeFalse();
});

test('a draft ignores the empty session, day and age group rows the form starts with', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), [
        'name' => 'Spring Camp',
        'location' => 'Eagles Facility',
        'is_published' => 0,
        'age_groups' => [['key' => 'group-new-1', 'name' => '', 'capacity' => '']],
        'sessions' => [[
            'name' => '',
            'description' => '',
            'price' => '',
            'day_price' => '',
            'days' => [['date' => '', 'label' => '', 'times' => [['age_group_key' => 'group-new-1', 'starts_at' => '', 'ends_at' => '']]]],
        ]],
    ])->assertSessionHasNoErrors();

    $camp = Camp::query()->sole();

    expect($camp->location)->toBe('Eagles Facility')
        ->and($camp->sessions()->count())->toBe(0)
        ->and($camp->ageGroups()->count())->toBe(0);
});

test('publishing with only the empty starter session asks for a session', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), [
        'name' => 'Spring Camp',
        'is_published' => 1,
        'sessions' => [['name' => '', 'price' => '', 'days' => [['date' => '', 'times' => [['starts_at' => '']]]]]],
    ])->assertSessionHasErrors(['sessions' => 'Add at least one session before publishing.']);
});

test('a camp address is saved and shown with a directions link', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), thanksgivingCampForm([
        'location' => 'Eagles Training Facility',
        'address' => '1234 Main St, Naperville, IL 60540',
    ]))->assertSessionHasNoErrors();

    $camp = Camp::query()->sole();

    $this->get(route('camps.show', $camp))
        ->assertInertia(fn ($page) => $page
            ->where('camp.address', '1234 Main St, Naperville, IL 60540')
            ->where('camp.map_url', 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Eagles Training Facility, 1234 Main St, Naperville, IL 60540')));
});

test('two age groups cannot share a name', function () {
    $form = thanksgivingCampForm();
    $form['age_groups'][] = ['key' => 'young-again', 'name' => '8–12u'];

    $this->actingAs($this->staff)->post(route('admin.camps.store'), $form)
        ->assertSessionHasErrors(['age_groups.2.name' => 'Each age group needs a different name.']);
});

test('removing an age group deletes it', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), thanksgivingCampForm());
    $camp = Camp::query()->sole();
    $schedule = $this->actingAs($this->staff)->get(route('admin.camps.edit', $camp))->viewData('page')['props']['camp']['schedule'];
    $older = array_pop($schedule['age_groups']);

    foreach ($schedule['sessions'] as &$session) {
        foreach ($session['days'] as &$day) {
            $day['times'] = array_values(array_filter($day['times'], fn (array $time): bool => $time['age_group_key'] !== $older['key']));
        }
    }
    unset($session, $day);

    $this->actingAs($this->staff)->put(route('admin.camps.update', $camp), thanksgivingCampForm($schedule))
        ->assertSessionHasNoErrors();

    expect($camp->ageGroups()->pluck('name')->all())->toBe(['8–12U'])
        ->and(CampAgeGroup::query()->whereKey($older['id'])->exists())->toBeFalse();
});

test('staff can preview a draft camp page, even with details missing', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), [
        'name' => 'Spring Camp',
        'is_published' => 0,
        'sessions' => [['name' => 'Pitching', 'days' => [['label' => 'Bullpens']]]],
    ]);
    $camp = Camp::query()->sole();

    $this->get(route('camps.show', $camp))->assertNotFound();
    $this->actingAs($this->staff)->get(route('camps.preview', $camp))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Camps/Show')
            ->where('preview.is_draft', true)
            ->where('camp.name', 'Spring Camp')
            ->where('camp.starts_at', null)
            ->where('camp.sessions.0.price', null)
            ->where('camp.sessions.0.days.0.title', 'Date TBD · Bullpens')
            ->where('seo.robots', 'noindex,nofollow'));

    expect($camp->refresh()->is_published)->toBeFalse();
});

test('guests cannot open a camp preview', function () {
    $camp = Camp::factory()->unpublished()->create();

    $this->get(route('camps.preview', $camp))->assertRedirect(route('login'));
});

test('staff can try the registration form on a draft and get live totals', function () {
    $this->actingAs($this->staff)->post(route('admin.camps.store'), thanksgivingCampForm(['is_published' => 0]));
    $camp = Camp::query()->sole();
    Coupon::factory()->fixedPerItem(2500)->create(['code' => 'THANKS30']);
    $young = $camp->ageGroups()->first();

    $this->get(route('camps.register', $camp))->assertNotFound();
    $this->postJson(route('camps.quote', $camp), ['days' => [1]])->assertNotFound();

    $this->actingAs($this->staff)->get(route('camps.preview.register', $camp))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('site/Camps/Register')
            ->where('preview.is_draft', true)
            ->where('quoteUrl', route('camps.preview.quote', $camp)));

    $this->actingAs($this->staff)->postJson(route('camps.preview.quote', $camp), [
        'days' => $camp->days()->pluck('camp_session_days.id')->all(),
        'camp_age_group_id' => $young->id,
        'coupon_code' => 'thanks30',
    ])->assertOk()->assertJson(['subtotal' => 25000, 'discount' => 5000, 'total' => 20000]);

    expect(Order::count())->toBe(0)
        ->and(CampRegistration::count())->toBe(0);
});

test('guests cannot use the preview registration form', function () {
    $camp = Camp::factory()->unpublished()->create();

    $this->get(route('camps.preview.register', $camp))->assertRedirect(route('login'));
    $this->postJson(route('camps.preview.quote', $camp), ['days' => [1]])->assertForbidden();
});
