<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Writes the admin activity log. Who did it is worked out per request: the
 * signed-in staff member, otherwise whatever actingAs() set (Stripe for
 * webhooks, System for scheduled jobs), otherwise "Customer" on the web and
 * "System" on the command line. Never log medical notes or payment details.
 */
class ActivityLogger
{
    public const string CUSTOMER = 'Customer';

    public const string STRIPE = 'Stripe';

    public const string SYSTEM = 'System';

    private ?string $actor = null;

    public function __construct(private AuthFactory $auth) {}

    /**
     * Name who is acting for the rest of this request or job (when nobody is signed in).
     */
    public function actingAs(string $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $properties  Extra detail shown on the log entry.
     */
    public function log(string $event, string $description, ?Model $subject = null, array $properties = []): ActivityLog
    {
        $user = $this->auth->guard()->user();
        /** A routed request is a web visit; queued jobs and scheduled commands have no route. */
        $request = app()->bound('request') ? app(Request::class) : null;
        $fromWeb = $request?->route() !== null;

        return ActivityLog::query()->create([
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'user_id' => $user?->getAuthIdentifier(),
            'actor' => $user->name ?? $this->actor ?? ($fromWeb ? self::CUSTOMER : self::SYSTEM),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $fromWeb && $this->actor === null ? $request->ip() : null,
            'created_at' => now(),
        ]);
    }
}
