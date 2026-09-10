<?php

use App\Models\User;
use App\Support\PortalAuth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

if (! function_exists('audit_context_properties')) {
    /**
     * Portal / branch / user_type properties for every activity row.
     *
     * @return array{portal: string|null, branch_id: int|null, user_type: string|null}
     */
    function audit_context_properties(): array
    {
        $guard = PortalAuth::activeGuard();
        $user = $guard !== null ? auth()->guard($guard)->user() : null;

        return [
            'portal' => $guard,
            'branch_id' => $user instanceof User ? $user->branch_id : null,
            'user_type' => $user instanceof User ? $user->user_type->value : null,
        ];
    }
}

if (! function_exists('audit')) {
    /**
     * Record a site-wide activity log entry with portal context properties.
     *
     * @param  array<string, mixed>  $properties
     */
    function audit(
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?string $event = null,
        ?string $logName = null,
    ): ?ActivityContract {
        $logger = activity($logName);

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        if ($event !== null) {
            $logger->event($event);
        }

        return $logger
            ->withProperties([
                ...audit_context_properties(),
                ...$properties,
            ])
            ->log($description);
    }
}
