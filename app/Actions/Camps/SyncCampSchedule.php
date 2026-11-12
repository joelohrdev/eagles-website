<?php

namespace App\Actions\Camps;

use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampDayTime;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Save a camp's age groups, sessions, days and times from the admin form:
 * rows with an id belonging to this camp are updated, rows without one are
 * created, and rows left out are deleted — except days and age groups that
 * players are booked into, which can't be removed. Then the camp's summary
 * dates and "from" price are recalculated from the schedule.
 */
class SyncCampSchedule
{
    /**
     * @param  array{age_groups: list<array{key: string, id: int|null, name: string, capacity: int|null}>, sessions: list<array{id: int|null, name: string, description: string|null, price: int|null, day_price: int|null, days: list<array{id: int|null, date: string|null, label: string|null, times: list<array{age_group_key: string|null, starts_at: string|null, ends_at: string|null}>}>}>}  $schedule
     *
     * @throws ValidationException when the change would remove a booked day or age group.
     */
    public function handle(Camp $camp, array $schedule): Camp
    {
        return DB::transaction(function () use ($camp, $schedule): Camp {
            $groupIdsByKey = $this->syncAgeGroups($camp, $schedule['age_groups']);
            $this->syncSessions($camp, $schedule['sessions'], $groupIdsByKey);

            return $this->syncSummary($camp);
        });
    }

    /**
     * @param  list<array{key: string, id: int|null, name: string, capacity: int|null}>  $groups
     * @return array<string, int> age group ids keyed by the form's key
     */
    private function syncAgeGroups(Camp $camp, array $groups): array
    {
        $existing = $camp->ageGroups()->get()->keyBy('id');
        $ids = [];

        foreach ($groups as $data) {
            /** @var CampAgeGroup $group */
            $group = $existing->get($data['id']) ?? new CampAgeGroup(['camp_id' => $camp->id]);
            $group->fill(['name' => $data['name'], 'capacity' => $data['capacity']])->save();
            $ids[$data['key']] = $group->id;
        }

        /** Eloquent's except() matches model keys and re-indexes, so read ids with modelKeys(), not keys(). */
        $removedIds = $existing->except(array_values($ids))->modelKeys();

        if ($removedIds !== [] && $camp->registrations()->whereIn('camp_age_group_id', $removedIds)->exists()) {
            throw ValidationException::withMessages(['age_groups' => __('An age group with registered players can’t be removed.')]);
        }

        CampAgeGroup::query()->whereKey($removedIds)->delete();

        return $ids;
    }

    /**
     * @param  list<array{id: int|null, name: string, description: string|null, price: int|null, day_price: int|null, days: list<array{id: int|null, date: string|null, label: string|null, times: list<array{age_group_key: string|null, starts_at: string|null, ends_at: string|null}>}>}>  $sessions
     * @param  array<string, int>  $groupIdsByKey
     */
    private function syncSessions(Camp $camp, array $sessions, array $groupIdsByKey): void
    {
        $existingSessions = $camp->sessions()->get()->keyBy('id');
        $existingDays = CampSessionDay::query()->whereIn('camp_session_id', $existingSessions->keys())->get()->keyBy('id');
        $keptSessionIds = [];
        $keptDayIds = [];

        foreach ($sessions as $s => $data) {
            /** @var CampSession $session */
            $session = $existingSessions->get($data['id']) ?? new CampSession(['camp_id' => $camp->id]);
            $session->fill(Arr::only($data, ['name', 'description', 'price', 'day_price']))->save();
            $keptSessionIds[] = $session->id;

            foreach ($data['days'] as $dayData) {
                /** @var CampSessionDay $day */
                $day = $existingDays->get($dayData['id']) ?? new CampSessionDay;
                $day->fill(['camp_session_id' => $session->id, 'date' => $dayData['date'], 'label' => $dayData['label']])->save();
                $keptDayIds[] = $day->id;

                $day->times()->delete();

                foreach ($dayData['times'] as $time) {
                    $day->times()->create([
                        'camp_age_group_id' => $time['age_group_key'] !== null ? $groupIdsByKey[$time['age_group_key']] : null,
                        'starts_at' => $time['starts_at'] !== null ? CampDayTime::normalize($time['starts_at']) : null,
                        'ends_at' => $time['ends_at'] !== null ? CampDayTime::normalize($time['ends_at']) : null,
                    ]);
                }
            }
        }

        $removedDayIds = $existingDays->keys()->diff($keptDayIds);

        if ($removedDayIds->isNotEmpty() && DB::table('camp_registration_days')->whereIn('camp_session_day_id', $removedDayIds)->exists()) {
            throw ValidationException::withMessages(['sessions' => __('A day with registered players can’t be removed. Move or delete those registrations first.')]);
        }

        CampSessionDay::query()->whereKey($removedDayIds)->delete();
        CampSession::query()->whereKey($existingSessions->keys()->diff($keptSessionIds))->delete();
    }

    /**
     * starts_at/ends_at span the schedule (first start to last end) and price is
     * the cheapest way in, so listings, SEO and the sitemap stay accurate. A
     * draft without dates yet has no start date.
     */
    private function syncSummary(Camp $camp): Camp
    {
        $times = CampDayTime::query()
            ->join('camp_session_days', 'camp_session_days.id', '=', 'camp_day_times.camp_session_day_id')
            ->join('camp_sessions', 'camp_sessions.id', '=', 'camp_session_days.camp_session_id')
            ->where('camp_sessions.camp_id', $camp->id)
            ->whereNotNull('camp_session_days.date')
            ->whereNotNull('camp_day_times.starts_at')
            ->get(['camp_session_days.date', 'camp_day_times.starts_at', 'camp_day_times.ends_at']);

        $starts = $times->map(fn (CampDayTime $time): Carbon => Carbon::parse(substr((string) $time->date, 0, 10).' '.$time->starts_at))->min();
        $ends = $times->map(fn (CampDayTime $time): Carbon => Carbon::parse(substr((string) $time->date, 0, 10).' '.($time->ends_at ?? $time->starts_at)))->max();

        $sessions = $camp->sessions()->get();
        $cheapest = $sessions
            ->map(fn (CampSession $session): ?int => collect([$session->price, $session->day_price])->filter(fn (?int $price): bool => $price !== null)->min())
            ->filter(fn (?int $price): bool => $price !== null)
            ->min() ?? 0;

        $camp->forceFill([
            'starts_at' => $starts,
            'ends_at' => $ends?->equalTo($starts) ? null : $ends,
            'price' => $cheapest,
        ])->save();

        $camp->unsetRelation('sessions')->unsetRelation('ageGroups')->refreshSlots();

        return $camp;
    }
}
