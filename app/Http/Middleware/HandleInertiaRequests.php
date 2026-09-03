<?php

namespace App\Http\Middleware;

use App\Services\Navigation;
use App\Services\SiteSettings;
use App\Services\TryoutAvailability;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private SiteSettings $settings,
        private Navigation $navigation,
        private TryoutAvailability $tryouts,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'isAdmin' => (bool) $user?->isAdmin(),
            ],
            'site' => fn () => $this->settings->only([
                'org_name', 'phone', 'email', 'address_line1', 'address_city', 'address_state', 'address_postal_code',
                'social_facebook', 'social_instagram', 'social_twitter', 'social_youtube', 'social_tiktok',
            ]),
            'navigation' => fn () => $this->navigation->forFrontend(),
            'tryoutsOpen' => fn (): bool => $this->tryouts->isOpen(),
            'cartCount' => fn () => (int) array_sum($request->session()->get('cart', [])),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
