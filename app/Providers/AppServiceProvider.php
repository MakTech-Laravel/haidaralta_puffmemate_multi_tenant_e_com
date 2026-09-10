<?php

namespace App\Providers;

use App\Listeners\AuthActivitySubscriber;
use App\Models\User;
use App\Support\OpsDashboardAccess;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;
use Laravel\Pulse\Facades\Pulse;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePasswordResetUrls();
        $this->configureActivityLogContext();
        $this->configureOpsDashboards();

        Event::subscribe(AuthActivitySubscriber::class);
    }

    protected function configureOpsDashboards(): void
    {
        // Pulse registers a local-only viewPulse gate via callAfterResolving.
        // Re-define after boot so platform OpsDashboardAccess always wins.
        $this->app->booted(function (): void {
            $this->callAfterResolving(GateContract::class, function (GateContract $gate): void {
                $gate->define('viewPulse', function (?User $user = null): bool {
                    return OpsDashboardAccess::allowsPulse(
                        $user ?? OpsDashboardAccess::resolvePortalUser(),
                    );
                });
            });
        });

        Pulse::user(fn ($user) => [
            'name' => $user->name,
            'extra' => $user->email,
            'avatar' => null,
        ]);
    }

    protected function configureActivityLogContext(): void
    {
        LogActivityAction::beforeLogging(function (ActivityContract $activity): void {
            $properties = collect($activity->properties?->toArray() ?? []);

            foreach (\audit_context_properties() as $key => $value) {
                if (! $properties->has($key)) {
                    $properties->put($key, $value);
                }
            }

            $activity->properties = $properties;
        });
    }

    protected function configurePasswordResetUrls(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
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
}
