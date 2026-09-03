<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\PageVisibility;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\StripeGateway;
use App\Services\SiteSettings;
use App\Services\TryoutAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $secret = config('services.stripe.secret');

            if (blank($secret) || $this->app->runningUnitTests()) {
                return new FakeGateway;
            }

            return new StripeGateway(
                new StripeClient($secret),
                (string) config('services.stripe.webhook_secret'),
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
}
