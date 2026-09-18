<?php

use App\Enums\PermissionEnum;
use App\Events\DatabaseBackupHistoryUpdated;
use App\Models\Conversation;
use App\Models\User;
use App\Support\MediaUrl;
use App\Support\PlatformTeamResolver;
use App\Support\PortalAuth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
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

/**
 * Order conversations reuse the exact policy the HTTP routes use, so a socket
 * can never reach a thread the operator could not open in the browser.
 *
 * This is a presence channel: the same subscription carries new messages, the
 * roster of who has the thread open, and `typing` whispers.
 *
 * @return array{id: int, name: string, avatar_url: string|null}|false
 */
Broadcast::channel(
    'conversation.{conversationId}',
    function (User $user, int $conversationId): array|false {
        $conversation = Conversation::query()->find($conversationId);

        if ($conversation === null) {
            return false;
        }

        if ($user->isBranch() && $user->branch_id !== null) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);
        }

        if ($user->isPlatform()) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
        }

        if (! Gate::forUser($user)->allows('view', $conversation)) {
            return false;
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
        ];
    },
    ['guards' => [...PortalAuth::PORTAL_GUARDS, 'web']],
);

/**
 * Per-user inbox stream so the conversation list reorders and re-badges while
 * the operator is looking at a different thread.
 */
Broadcast::channel(
    'conversation-inbox.{userId}',
    function (User $user, int $userId): bool {
        return (int) $user->id === $userId;
    },
    ['guards' => [...PortalAuth::PORTAL_GUARDS, 'web']],
);
