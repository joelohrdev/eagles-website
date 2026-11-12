<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\TryoutRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin activity log: who did what and when, newest first. Filter by kind
 * of event, search text, or one registration's or order's history (which
 * includes its order's or registration's events too).
 */
class ActivityLogController extends Controller
{
    /**
     * Event-name prefixes for each filter tab.
     *
     * @var array<string, array{label: string, prefixes: list<string>}>
     */
    public const array CATEGORIES = [
        'registrations' => ['label' => 'Registrations', 'prefixes' => ['camp_registration.', 'tryout_registration.']],
        'payments' => ['label' => 'Orders & payments', 'prefixes' => ['order.']],
        'camps' => ['label' => 'Camps', 'prefixes' => ['camp.']],
        'coupons' => ['label' => 'Coupons', 'prefixes' => ['coupon.']],
    ];

    public function index(Request $request): Response
    {
        $category = (string) $request->query('category', '');
        $search = trim((string) $request->query('q', ''));
        $registration = $request->integer('registration') ?: null;
        $order = $request->integer('order') ?: null;

        $logs = ActivityLog::query()
            ->when(self::CATEGORIES[$category] ?? null, fn (Builder $query, array $cat) => $query->where(function (Builder $q) use ($cat): void {
                foreach ($cat['prefixes'] as $prefix) {
                    $q->orWhere('event', 'like', $prefix.'%');
                }
            }))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('description', 'like', "%{$search}%")
                ->orWhere('actor', 'like', "%{$search}%")
                ->orWhere('properties', 'like', "%{$search}%")))
            ->when($registration, fn (Builder $query, int $id) => self::forRegistration($query, $id))
            ->when($order, fn (Builder $query, int $id) => self::forOrder($query, $id))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (ActivityLog $log): array => self::present($log));

        return Inertia::render('admin/activity/Index', [
            'logs' => $logs,
            'filters' => ['category' => $category, 'q' => $search, 'registration' => $registration, 'order' => $order],
            'categories' => collect(self::CATEGORIES)->map(fn (array $cat, string $key): array => ['value' => $key, 'label' => $cat['label']])->values()->all(),
            'scope' => match (true) {
                $registration !== null => 'History for one registration',
                $order !== null => 'History for one order',
                default => null,
            },
        ]);
    }

    /**
     * A camp registration's events plus its order's (payment, cancellation, refund).
     *
     * @param  Builder<ActivityLog>  $query
     */
    private static function forRegistration(Builder $query, int $registrationId): void
    {
        $orderId = CampRegistration::query()->whereKey($registrationId)->value('order_id');

        $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $r) => $r->where('subject_type', (new CampRegistration)->getMorphClass())->where('subject_id', $registrationId))
            ->when($orderId, fn (Builder $r) => $r->orWhere(fn (Builder $o) => $o->where('subject_type', (new Order)->getMorphClass())->where('subject_id', $orderId))));
    }

    /**
     * An order's events plus its camp registration's.
     *
     * @param  Builder<ActivityLog>  $query
     */
    private static function forOrder(Builder $query, int $orderId): void
    {
        $registrationId = CampRegistration::query()->where('order_id', $orderId)->value('id');

        $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $o) => $o->where('subject_type', (new Order)->getMorphClass())->where('subject_id', $orderId))
            ->when($registrationId, fn (Builder $r) => $r->orWhere(fn (Builder $c) => $c->where('subject_type', (new CampRegistration)->getMorphClass())->where('subject_id', $registrationId))));
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'event' => $log->event,
            'description' => $log->description,
            'actor' => $log->actor,
            'is_staff' => $log->user_id !== null,
            'properties' => $log->properties ?? [],
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at,
            'link' => self::linkFor($log),
        ];
    }

    /**
     * Where to look at the record now, if it still exists.
     *
     * @return array{label: string, url: string}|null
     */
    private static function linkFor(ActivityLog $log): ?array
    {
        if ($log->subject_type === null || $log->subject_id === null || str_ends_with($log->event, '.deleted')) {
            return null;
        }

        return match ($log->subject_type) {
            (new Order)->getMorphClass() => Order::query()->whereKey($log->subject_id)->exists()
                ? ['label' => 'View order', 'url' => route('admin.orders.show', $log->subject_id)] : null,
            (new CampRegistration)->getMorphClass() => ($registration = CampRegistration::query()->with('camp:id,slug')->find($log->subject_id))
                ? ['label' => 'View registration', 'url' => route('admin.camps.registrations.index', ['camp' => $registration->camp, 'q' => $registration->email])] : null,
            (new TryoutRegistration)->getMorphClass() => ($registration = TryoutRegistration::query()->with('tryout:id,slug')->find($log->subject_id))
                ? ['label' => 'View registration', 'url' => route('admin.tryouts.registrations.index', $registration->tryout)] : null,
            (new Camp)->getMorphClass() => ($camp = Camp::query()->find($log->subject_id, ['id', 'slug']))
                ? ['label' => 'View camp', 'url' => route('admin.camps.edit', $camp)] : null,
            (new Coupon)->getMorphClass() => Coupon::query()->whereKey($log->subject_id)->exists()
                ? ['label' => 'View coupon', 'url' => route('admin.coupons.edit', $log->subject_id)] : null,
            default => null,
        };
    }
}
