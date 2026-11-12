<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PageVisibility;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\StripeGateway;
use App\Services\Payments\UnavailableGateway;
use App\Services\SiteSettings;
use App\Services\StripeSettings;
use App\Services\TryoutAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
        $this->app->singleton(PageVisibility::class);
        $this->app->scoped(TryoutAvailability::class);
        /** Scoped so an actingAs() (e.g. "Stripe" for a webhook) lasts one request or job only. */
        $this->app->scoped(ActivityLogger::class);

        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $stripe = $this->app->make(StripeSettings::class);
            $secret = $stripe->secretKey();

            if ($this->app->runningUnitTests() || (blank($secret) && $this->app->environment('local', 'testing'))) {
                return new FakeGateway;
            }

            /** Never fall back to fake payments on a real site: switch payments off instead. */
            if (blank($secret)) {
                return new UnavailableGateway;
            }

            return new StripeGateway(
                new StripeClient($secret),
                (string) $stripe->webhookSecret(),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Role-based abilities. All authenticated users are staff; admins can also manage users and settings.
     */
    protected function configureGates(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->role === UserRole::Admin);
    }

    /**
     * Named limiters, so they keep their own counters (plain throttle:x,y
     * shares one counter per visitor across every route that uses it).
     */
    protected function configureRateLimiting(): void
    {
        /** A 4-digit camp preview passcode has 10,000 options: allow 5 guesses a minute per visitor and camp. */
        RateLimiter::for('camp-preview-unlock', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip().'|'.$request->route()?->originalParameter('camp')));
    }
}
