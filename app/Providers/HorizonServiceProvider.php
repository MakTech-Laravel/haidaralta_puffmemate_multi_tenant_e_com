<?php

namespace App\Providers;

use App\Models\User;
use App\Support\OpsDashboardAccess;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        $mailTo = env('HORIZON_MAIL_TO') ?: env('BACKUP_MAIL_TO');

        if (filled($mailTo)) {
            Horizon::routeMailNotificationsTo($mailTo);
        }
    }

    /**
     * Configure the Horizon authorization services.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request): bool {
            return OpsDashboardAccess::allowsHorizon(
                OpsDashboardAccess::resolvePortalUser($request),
            );
        });
    }

    /**
     * Register the Horizon gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user = null): bool {
            return OpsDashboardAccess::allowsHorizon(
                $user ?? OpsDashboardAccess::resolvePortalUser(),
            );
        });
    }
}
