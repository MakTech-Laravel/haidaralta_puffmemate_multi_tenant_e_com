<?php

namespace App\Support;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class SuperAdmin
{
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
