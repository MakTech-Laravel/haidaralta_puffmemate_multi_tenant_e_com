<?php

namespace App\Support;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class SuperAdmin
{
    public const LAST_ACTIVE_BRANCH_MESSAGE = 'Cannot remove the last super-admin of an active branch. Deactivate or delete the branch, or assign another super-admin first.';

    /**
     * Active branches must keep at least one active super-admin. Inactive branches do not.
     */
    public static function isLastInActiveBranch(User $user, Branch $branch): bool
    {
        if (! $branch->status) {
            return false;
        }

        return self::isLastInTeam($user, $branch->id);
    }

    /**
     * Count active users with the super-admin role in the given Spatie team.
     */
    public static function countInTeam(int $teamId, ?int $exceptUserId = null): int
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $userType = $teamId === PlatformTeamResolver::PLATFORM_TEAM_ID
            ? UserType::PLATFORM
            : UserType::BRANCH;

        $query = User::query()
            ->where('user_type', $userType)
            ->where('is_active', true)
            ->when(
                $userType === UserType::BRANCH,
                fn ($builder) => $builder->where('branch_id', $teamId),
            )
            ->when(
                $exceptUserId !== null,
                fn ($builder) => $builder->whereKeyNot($exceptUserId),
            );

        return $query
            ->get()
            ->filter(fn (User $user): bool => $user->hasRole(RoleEnum::SUPER_ADMIN->value))
            ->count();
    }

    public static function isLastInTeam(User $user, int $teamId): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        if (! $user->hasRole(RoleEnum::SUPER_ADMIN->value) || ! $user->is_active) {
            return false;
        }

        return self::countInTeam($teamId, $user->id) === 0;
    }
}
