<?php

use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampDayTime;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A valid public camp registration that books every day of the camp.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validCampRegistration(Camp $camp, array $overrides = []): array
{
    return array_merge([
        'days' => $camp->days()->pluck('camp_session_days.id')->all(),
        'player_first_name' => 'Jamie',
        'player_last_name' => 'Rivera',
        'player_birthdate' => now()->subYears(12)->toDateString(),
        'parent_name' => 'Alex Rivera',
        'email' => 'alex@example.com',
        'phone' => '630-555-0100',
        'emergency_contact_name' => 'Sam Rivera',
        'emergency_contact_phone' => '630-555-0101',
        'medical_notes' => 'Peanut allergy',
    ], $overrides);
}

/**
 * The Thanksgiving camp: Pitching (Nov 21 + 23, $125, $75 a day) and Fielding & Hitting
 * (Nov 24 + 25, $125, whole session only), $225 for both, two age groups.
 *
 * @return array{camp: Camp, young: CampAgeGroup, older: CampAgeGroup, pitching: CampSession, fielding: CampSession}
 */
function thanksgivingCamp(?int $olderCapacity = null): array
{
    $camp = Camp::factory()->withoutSchedule()->create(['name' => 'Thanksgiving Camp', 'price' => 7500, 'bundle_price' => 22500, 'capacity' => null]);
    $young = CampAgeGroup::factory()->for($camp)->create(['name' => '8–12U']);
    $older = CampAgeGroup::factory()->for($camp)->create(['name' => '13–17U', 'capacity' => $olderCapacity]);

    $pitching = CampSession::factory()->for($camp)->singleDays(7500)->create(['name' => 'Pitching', 'price' => 12500]);
    $fielding = CampSession::factory()->for($camp)->create(['name' => 'Fielding & Hitting', 'price' => 12500]);

    foreach ([[$pitching, '2026-11-21', null], [$pitching, '2026-11-23', null], [$fielding, '2026-11-24', 'Fielding'], [$fielding, '2026-11-25', 'Hitting']] as [$session, $date, $label]) {
        $day = CampSessionDay::factory()->for($session, 'session')->create(['date' => $date, 'label' => $label]);
        CampDayTime::factory()->for($day, 'day')->create(['camp_age_group_id' => $young->id, 'starts_at' => '17:30:00', 'ends_at' => '19:00:00']);
        CampDayTime::factory()->for($day, 'day')->create(['camp_age_group_id' => $older->id, 'starts_at' => '19:00:00', 'ends_at' => '20:30:00']);
    }

    return ['camp' => $camp->fresh(), 'young' => $young, 'older' => $older, 'pitching' => $pitching, 'fielding' => $fielding];
}

/**
 * @param  iterable<CampSession>  $sessions
 * @return list<int>
 */
function dayIdsOf(iterable $sessions): array
{
    return collect($sessions)->flatMap(fn (CampSession $session) => $session->days()->pluck('id'))->all();
}
