<?php

use App\Enums\PermissionEnum;
use App\Events\DatabaseBackupHistoryUpdated;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use App\Support\PortalAuth;
use Illuminate\Support\Facades\Broadcast;
use Spatie\Permission\PermissionRegistrar;

Broadcast::channel(
    'App.Models.User.{id}',
    function (User $user, int $id): bool {
        return (int) $user->id === $id;
    },
    ['guards' => [...PortalAuth::PORTAL_GUARDS, 'web']],
);

Broadcast::channel(
    DatabaseBackupHistoryUpdated::CHANNEL,
    function (User $user): bool {
        if (! $user->isPlatform()) {
            return false;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

        // Super-admin is granted via Gate::before; others need the Spatie permission.
        return $user->can(PermissionEnum::MANAGE_DATABASE_BACKUPS->value);
    },
    ['guards' => [...PortalAuth::PORTAL_GUARDS, 'web']],
);
