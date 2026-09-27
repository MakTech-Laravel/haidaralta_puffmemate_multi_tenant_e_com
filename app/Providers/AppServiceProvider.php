<?php

namespace App\Providers;

use App\Contracts\SendsSms;
use App\Listeners\ApplyMailReplyTo;
use App\Listeners\AuthActivitySubscriber;
use App\Models\User;
use App\Services\Application\ApplicationSettingsService;
use App\Services\Application\SmtpSettingsService;
use App\Services\Sms\FakeSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Support\OpsDashboardAccess;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->app->singleton(SendsSms::class, function (): SendsSms {
            if ($this->app->environment('testing')) {
                return new FakeSmsSender;
            }

            return new LogSmsSender;
        });

        $this->app->singleton(FakeSmsSender::class, function ($app): FakeSmsSender {
            $sender = $app->make(SendsSms::class);

            return $sender instanceof FakeSmsSender
                ? $sender
                : new FakeSmsSender;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureApplicationSettingsOverlays();
        $this->configureActivityLogContext();
        $this->configureOpsDashboards();
        $this->configureRateLimiters();

        Event::subscribe(AuthActivitySubscriber::class);
        Event::listen(MessageSending::class, ApplyMailReplyTo::class);
    }

    protected function configureApplicationSettingsOverlays(): void
    {
        try {
            app(ApplicationSettingsService::class)->bootRuntimeOverlay();
            app(SmtpSettingsService::class)->bootRuntimeOverlay();
        } catch (\Throwable) {
            // Tables may not exist yet during early migrate/install.
        }
    }

    /**
     * Cap chat sends so a stuck client cannot flood a thread.
     * Pace announcement SMTP so Mailtrap Sandbox Free (1 / 10s) is not flooded.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('conversation-messages', function (Request $request): Limit {
            $user = $request->user();

            return $user === null
                ? Limit::perMinute(10)->by($request->ip())
                : Limit::perMinute(30)->by((string) $user->getAuthIdentifier());
        });

        RateLimiter::for('announcement-mail', function (): Limit {
            if ($this->app->environment('testing')) {
                return Limit::none();
            }

            $maxAttempts = max(1, (int) config('mail.announcement.max_attempts'));
            $decaySeconds = max(1, (int) config('mail.announcement.decay_seconds'));

            return Limit::perSecond($maxAttempts, $decaySeconds)->by('announcement-mail');
        });
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
            'extra' => $user->phone ?? $user->email,
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
