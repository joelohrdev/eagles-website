<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A camp and its schedule. The schedule is a list of sessions, each with one or
 * more days, and each day has a time per age group (or a single time when the
 * camp has no age groups). Age groups are referenced from times by `key`,
 * because new groups have no id until they are saved.
 *
 * Unpublished camps are drafts: only a name is required, and sessions, days
 * and times may be saved half-filled. Publishing requires the full schedule.
 */
class StoreCampRequest extends FormRequest
{
    use HasSeoRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The form starts with an empty session, day and time rows, and adding an age
     * group adds an empty row too. Rows nobody typed into are dropped here, so a
     * draft saves without asking for a session name nobody has entered yet.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'age_groups' => collect($this->input('age_groups', []))
                ->filter(fn ($group): bool => is_array($group) && (filled($group['name'] ?? null) || filled($group['capacity'] ?? null)))
                ->values()
                ->all(),
            'sessions' => collect($this->input('sessions', []))
                ->filter(fn ($session): bool => is_array($session))
                ->map(fn (array $session): array => [
                    ...$session,
                    'days' => collect($session['days'] ?? [])
                        ->filter(fn ($day): bool => is_array($day) && ! self::isBlankDay($day))
                        ->values()
                        ->all(),
                ])
                ->reject(fn (array $session): bool => blank($session['id'] ?? null)
                    && blank($session['name'] ?? null)
                    && blank($session['description'] ?? null)
                    && blank($session['price'] ?? null)
                    && blank($session['day_price'] ?? null)
                    && $session['days'] === [])
                ->values()
                ->all(),
        ]);
    }

    /**
     * A new day with no date, label or times entered.
     *
     * @param  array<string, mixed>  $day
     */
    private static function isBlankDay(array $day): bool
    {
        return blank($day['id'] ?? null)
            && blank($day['date'] ?? null)
            && blank($day['label'] ?? null)
            && collect($day['times'] ?? [])->every(fn ($time): bool => ! is_array($time) || (blank($time['starts_at'] ?? null) && blank($time['ends_at'] ?? null)));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isPublishing() ? 'required' : 'nullable';

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'age_range' => ['nullable', 'string', 'max:100'],
            'bundle_price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],

            'age_groups' => ['nullable', 'array', 'max:10'],
            'age_groups.*.key' => ['required', 'string', 'max:40', 'distinct'],
            'age_groups.*.id' => ['nullable', 'integer'],
            'age_groups.*.name' => ['required', 'string', 'max:50', 'distinct:ignore_case'],
            'age_groups.*.capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],

            'sessions' => [$required, 'array', $this->isPublishing() ? 'min:1' : 'min:0', 'max:10'],
            'sessions.*.id' => ['nullable', 'integer'],
            'sessions.*.name' => ['required', 'string', 'max:100'],
            'sessions.*.description' => ['nullable', 'string', 'max:1000'],
            'sessions.*.price' => [$required, 'numeric', 'min:0', 'max:100000'],
            'sessions.*.day_price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'sessions.*.days' => [$required, 'array', $this->isPublishing() ? 'min:1' : 'min:0', 'max:14'],
            'sessions.*.days.*.id' => ['nullable', 'integer'],
            'sessions.*.days.*.date' => [$required, 'date_format:Y-m-d'],
            'sessions.*.days.*.label' => ['nullable', 'string', 'max:50'],
            'sessions.*.days.*.times' => [$required, 'array', 'max:10'],
            'sessions.*.days.*.times.*.age_group_key' => ['nullable', 'string', 'max:40'],
            'sessions.*.days.*.times.*.starts_at' => [$required, 'date_format:H:i'],
            'sessions.*.days.*.times.*.ends_at' => ['nullable', 'date_format:H:i'],

            ...$this->seoRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_closes_at.after' => 'Registration must close after it opens.',
            'age_groups.*.name.distinct' => 'Each age group needs a different name.',
            'sessions.required' => 'Add at least one session before publishing.',
            'sessions.*.price.required' => 'Enter a price before publishing.',
            'sessions.*.days.required' => 'Each session needs at least one day before publishing.',
            'sessions.*.days.*.date.required' => 'Pick a date before publishing.',
            'sessions.*.days.*.times.*.starts_at.required' => 'Enter a start time before publishing.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'age_groups.*.name' => 'age group name',
            'age_groups.*.capacity' => 'age group capacity',
            'sessions.*.name' => 'session name',
            'sessions.*.price' => 'session price',
            'sessions.*.day_price' => 'single-day price',
            'sessions.*.days.*.date' => 'date',
            'sessions.*.days.*.label' => 'day label',
            'sessions.*.days.*.times.*.starts_at' => 'start time',
            'sessions.*.days.*.times.*.ends_at' => 'end time',
        ];
    }

    /**
     * Every day needs exactly one time per age group (or one time when there are
     * no age groups), and each time must end after it starts.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->checkTimes($validator);
            },
        ];
    }

    /**
     * A draft only needs its times to make sense where both ends are filled in;
     * publishing also needs a start time for every age group on every day.
     */
    private function checkTimes(Validator $validator): void
    {
        $groupKeys = collect($this->input('age_groups', []))->pluck('key')->map(fn ($key) => (string) $key)->sort()->values()->all();
        $expected = $groupKeys === [] ? [null] : $groupKeys;

        foreach ($this->input('sessions', []) as $s => $session) {
            foreach ($session['days'] ?? [] as $d => $day) {
                $times = collect($day['times'] ?? []);
                $keys = $times
                    ->filter(fn (array $time): bool => filled($time['starts_at'] ?? null))
                    ->map(fn (array $time) => filled($time['age_group_key'] ?? null) ? (string) $time['age_group_key'] : null);

                if ($this->isPublishing() && $keys->sort()->values()->all() !== $expected) {
                    $validator->errors()->add("sessions.{$s}.days.{$d}.times", $groupKeys === []
                        ? 'Enter a time for this day before publishing.'
                        : 'Enter a time for every age group on this day before publishing.');
                }

                foreach ($times as $t => $time) {
                    if (filled($time['ends_at'] ?? null) && filled($time['starts_at'] ?? null) && $time['ends_at'] <= $time['starts_at']) {
                        $validator->errors()->add("sessions.{$s}.days.{$d}.times.{$t}.ends_at", 'The end time must be after the start time.');
                    }
                }
            }
        }
    }

    /**
     * Saving with "Publish" (or saving a camp that is already live) needs the full schedule.
     */
    public function isPublishing(): bool
    {
        return $this->boolean('is_published');
    }

    /**
     * Validated camp attributes ready for mass assignment. Dates and the "from"
     * price come from the schedule (SyncCampSchedule), not from the form.
     *
     * @return array<string, mixed>
     */
    public function campAttributes(): array
    {
        $data = $this->safe()->only([
            'name', 'description', 'location', 'address', 'age_range',
            'capacity', 'registration_opens_at', 'registration_closes_at', 'youtube_url',
        ]);

        $data['bundle_price'] = self::toCents($this->validated('bundle_price'));
        $data['is_published'] = $this->boolean('is_published');

        return $data;
    }

    /**
     * The validated schedule with prices in cents.
     *
     * @return array{age_groups: list<array{key: string, id: int|null, name: string, capacity: int|null}>, sessions: list<array{id: int|null, name: string, description: string|null, price: int|null, day_price: int|null, days: list<array{id: int|null, date: string|null, label: string|null, times: list<array{age_group_key: string|null, starts_at: string|null, ends_at: string|null}>}>}>}
     */
    public function schedule(): array
    {
        /** Times for an age group that was left blank (and so dropped) have nowhere to go. */
        $groupKeys = collect($this->validated('age_groups') ?? [])->map(fn (array $group): string => (string) $group['key'])->all();

        return [
            'age_groups' => collect($this->validated('age_groups') ?? [])->map(fn (array $group): array => [
                'key' => (string) $group['key'],
                'id' => isset($group['id']) ? (int) $group['id'] : null,
                'name' => $group['name'],
                'capacity' => isset($group['capacity']) ? (int) $group['capacity'] : null,
            ])->values()->all(),
            'sessions' => collect($this->validated('sessions') ?? [])->map(fn (array $session): array => [
                'id' => isset($session['id']) ? (int) $session['id'] : null,
                'name' => $session['name'],
                'description' => $session['description'] ?? null,
                'price' => self::toCents($session['price'] ?? null),
                'day_price' => self::toCents($session['day_price'] ?? null),
                'days' => collect($session['days'] ?? [])->map(fn (array $day): array => [
                    'id' => isset($day['id']) ? (int) $day['id'] : null,
                    'date' => $day['date'] ?? null,
                    'label' => $day['label'] ?? null,
                    'times' => collect($day['times'] ?? [])
                        ->filter(fn (array $time): bool => filled($time['starts_at'] ?? null) || filled($time['ends_at'] ?? null))
                        ->filter(fn (array $time): bool => blank($time['age_group_key'] ?? null) ? $groupKeys === [] : in_array((string) $time['age_group_key'], $groupKeys, true))
                        ->map(fn (array $time): array => [
                            'age_group_key' => filled($time['age_group_key'] ?? null) ? (string) $time['age_group_key'] : null,
                            'starts_at' => $time['starts_at'] ?? null,
                            'ends_at' => $time['ends_at'] ?? null,
                        ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * The earliest day and time in the submitted schedule, used as the new
     * camp's start until SyncCampSchedule recalculates it.
     */
    public function scheduleStartsAt(): ?string
    {
        return collect($this->schedule()['sessions'])
            ->flatMap(fn (array $session) => $session['days'])
            ->filter(fn (array $day): bool => $day['date'] !== null)
            ->flatMap(fn (array $day) => collect($day['times'])
                ->filter(fn (array $time): bool => $time['starts_at'] !== null)
                ->map(fn (array $time): string => "{$day['date']} {$time['starts_at']}"))
            ->min();
    }

    private static function toCents(mixed $dollars): ?int
    {
        return blank($dollars) ? null : (int) round(((float) $dollars) * 100);
    }
}
