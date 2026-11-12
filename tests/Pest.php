<?php

use App\Models\Camp;
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
