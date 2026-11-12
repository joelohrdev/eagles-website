<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\CampAgeGroup;
use App\Models\CampRegistration;
use App\Models\CampSession;
use App\Models\CampSessionDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampRegistrationController extends Controller
{
    public function index(Request $request, Camp $camp): Response
    {
        $filters = $this->filters($request);
        $camp->loadSchedule();

        $registrations = $this->query($camp, $filters)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CampRegistration $registration) => [
                ...$registration->only(['id', 'player_first_name', 'player_last_name', 'player_birthdate', 'parent_name', 'email', 'phone', 'emergency_contact_name', 'emergency_contact_phone', 'medical_notes', 'registered_at', 'expires_at']),
                'player_name' => $registration->playerName(),
                'status' => $registration->status->value,
                'status_label' => $registration->status->label(),
                'order_number' => $registration->order?->number,
                'order_id' => $registration->order_id,
                'age_group' => $registration->ageGroup?->name,
                'schedule' => $registration->scheduleLines(),
                /** The coupon code (or "Multi-session price") and amount taken off, when the order had a discount. */
                'discount_label' => $registration->order?->discount > 0 ? $registration->order->coupon_code : null,
                'discount' => $registration->order?->discount ?? 0,
            ]);

        return Inertia::render('admin/camps/Registrations', [
            'camp' => [
                ...$camp->only(['id', 'name', 'slug', 'starts_at', 'capacity', 'price']),
                'spots_remaining' => $camp->spotsRemaining(),
                'paid_count' => $camp->registrations()->paid()->count(),
            ],
            'registrations' => $registrations,
            'filters' => $filters,
            'sessions' => $camp->sessions->map(fn (CampSession $session): array => ['id' => $session->id, 'name' => $session->name])->all(),
            'days' => $camp->sessions->flatMap(fn (CampSession $session) => $session->days->map(fn (CampSessionDay $day): array => [
                'id' => $day->id,
                'label' => "{$session->name}: {$day->title()}",
            ]))->values()->all(),
            'ageGroups' => $camp->ageGroups->map(fn (CampAgeGroup $group): array => ['id' => $group->id, 'name' => $group->name])->all(),
            'statuses' => collect(RegistrationStatus::cases())->map(fn (RegistrationStatus $s) => ['value' => $s->value, 'label' => $s->label()])->all(),
        ]);
    }

    public function export(Request $request, Camp $camp): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'camp-'.$camp->slug.'-registrations-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($camp, $filters): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Player', 'Birthdate', 'Age group', 'Days', 'Parent/Guardian', 'Email', 'Phone', 'Emergency contact', 'Emergency phone', 'Medical notes', 'Status', 'Order number', 'Discount', 'Discount amount', 'Paid', 'Registered at']);

            $this->query($camp, $filters)->lazy()->each(function (CampRegistration $registration) use ($handle): void {
                fputcsv($handle, [
                    $registration->playerName(),
                    $registration->player_birthdate->toDateString(),
                    $registration->ageGroup?->name,
                    implode('; ', $registration->scheduleLines()),
                    $registration->parent_name,
                    $registration->email,
                    $registration->phone,
                    $registration->emergency_contact_name,
                    $registration->emergency_contact_phone,
                    $registration->medical_notes,
                    $registration->status->label(),
                    $registration->order?->number,
                    $registration->order?->discount > 0 ? $registration->order->coupon_code : null,
                    $registration->order?->discount > 0 ? number_format($registration->order->discount / 100, 2, '.', '') : null,
                    $registration->order ? number_format($registration->order->total / 100, 2, '.', '') : null,
                    $registration->registered_at->format('Y-m-d H:i'),
                ]);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * A day-by-day roster for the camp lead: one row per player per booked day,
     * sorted by date, session, age group and time, with contacts and medical
     * notes. Honours the page's filters (day, session, age group, search,
     * status); with no status chosen it lists paid registrations only, the
     * players actually expected to show up.
     */
    public function roster(Request $request, Camp $camp): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->query($camp, $filters)
            ->when($filters['status'] === '', fn (Builder $query) => $query->where('status', RegistrationStatus::Paid));

        $rows = $query->get()
            ->flatMap(fn (CampRegistration $registration) => $registration->days
                ->filter(fn (CampSessionDay $day): bool => ($filters['day'] === null || $day->id === $filters['day'])
                    && ($filters['session'] === null || $day->camp_session_id === $filters['session']))
                ->map(fn (CampSessionDay $day): array => [
                    'registration' => $registration,
                    'day' => $day,
                    'time' => $day->timeFor($registration->camp_age_group_id) ?? $day->times->first(),
                ]))
            ->sortBy([
                fn (array $a, array $b): int => strcmp((string) $a['day']->date?->toDateString(), (string) $b['day']->date?->toDateString()),
                fn (array $a, array $b): int => strcmp($a['day']->session->name, $b['day']->session->name),
                /** Age groups in the order they were set up (by id), not by name: "13–17U" would sort before "8–12U". */
                fn (array $a, array $b): int => ($a['registration']->camp_age_group_id ?? 0) <=> ($b['registration']->camp_age_group_id ?? 0),
                fn (array $a, array $b): int => strcmp((string) $a['time']?->starts_at, (string) $b['time']?->starts_at),
                fn (array $a, array $b): int => strcmp($a['registration']->player_last_name.' '.$a['registration']->player_first_name, $b['registration']->player_last_name.' '.$b['registration']->player_first_name),
            ])
            ->values();

        $filename = 'camp-'.$camp->slug.'-daily-roster-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Date', 'Day', 'Session', 'Age group', 'Time', 'Player', 'Birthdate', 'Parent/Guardian', 'Phone', 'Email', 'Emergency contact', 'Emergency phone', 'Medical notes', 'Status', 'Order number']);

            foreach ($rows as ['registration' => $registration, 'day' => $day, 'time' => $time]) {
                fputcsv($handle, [
                    $day->date?->toDateString() ?? 'TBD',
                    $day->date?->format('l') ?? '',
                    $day->session->name.($day->label ? " ({$day->label})" : ''),
                    $registration->ageGroup?->name,
                    $time?->range(),
                    $registration->playerName(),
                    $registration->player_birthdate->toDateString(),
                    $registration->parent_name,
                    $registration->phone,
                    $registration->email,
                    $registration->emergency_contact_name,
                    $registration->emergency_contact_phone,
                    $registration->medical_notes,
                    $registration->status->label(),
                    $registration->order?->number,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(Camp $camp, CampRegistration $registration): RedirectResponse
    {
        $registration->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Registration deleted.')]);

        return back();
    }

    /**
     * The roster filters from the query string, shared by the page and both exports.
     *
     * @return array{q: string, status: string, day: int|null, session: int|null, age_group: int|null}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'day' => $request->integer('day') ?: null,
            'session' => $request->integer('session') ?: null,
            'age_group' => $request->integer('age_group') ?: null,
        ];
    }

    /**
     * @param  array{q: string, status: string, day: int|null, session: int|null, age_group: int|null}  $filters
     * @return Builder<CampRegistration>
     */
    private function query(Camp $camp, array $filters): Builder
    {
        $search = $filters['q'];

        return CampRegistration::query()
            ->whereBelongsTo($camp)
            ->with(['order', 'ageGroup', 'days.session', 'days.times'])
            ->when($filters['day'], fn (Builder $query, int $id) => $query->whereHas('days', fn (Builder $days) => $days->whereKey($id)))
            ->when($filters['session'], fn (Builder $query, int $id) => $query->whereHas('days', fn (Builder $days) => $days->where('camp_session_id', $id)))
            ->when($filters['age_group'], fn (Builder $query, int $id) => $query->where('camp_age_group_id', $id))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('player_first_name', 'like', "%{$search}%")
                ->orWhere('player_last_name', 'like', "%{$search}%")
                ->orWhere('parent_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when(RegistrationStatus::tryFrom($filters['status']), fn (Builder $query, RegistrationStatus $s) => $query->where('status', $s))
            ->latest('registered_at')
            ->orderByDesc('id');
    }
}
