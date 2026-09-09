<?php

namespace App\Providers;

use App\Models\User;
use App\Support\PortalAuth;
use App\Support\TelescopeAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local', 'staging');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Configure the Telescope authorization services.
     */
    protected function authorization(): void
    {
        $this->gate();

        Telescope::auth(function ($request) {
            if (! $this->app->environment('local', 'staging')) {
                return false;
            }

            // Local: allow without login so Telescope can debug auth failures.
            // Staging: platform super-admins only.
            if ($this->app->environment('local')) {
                return true;
            }

            return TelescopeAccess::allows($this->resolveTelescopeUser($request));
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * Access is limited to platform super-admins in local/staging only.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (?User $user): bool {
            return TelescopeAccess::allows($user);
        });
    }

    private function resolveTelescopeUser($request): ?User
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $user;
        }

        foreach (PortalAuth::PORTAL_GUARDS as $guard) {
            $candidate = Auth::guard($guard)->user();

            if ($candidate instanceof User) {
                return $candidate;
            }
        }

        return null;
    }
}
