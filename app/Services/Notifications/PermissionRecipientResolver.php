<?php

namespace App\Services\Notifications;

use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Resolves active staff ids that hold a permission inside a Spatie team
 * (platform team or one branch), restoring the caller's team afterwards.
 */
class PermissionRecipientResolver
{
    public function __construct(private PermissionRegistrar $registrar) {}

    /**
     * @return list<int>
     */
    public function platform(PermissionEnum|string $permission): array
    {
        return $this->withinTeam(
            PlatformTeamResolver::PLATFORM_TEAM_ID,
            fn (): array => $this->idsWithPermission(
                User::query()->where('user_type', UserType::PLATFORM),
                $permission,
            ),
        );
    }

    /**
     * @return list<int>
     */
    public function branch(int $branchId, PermissionEnum|string $permission): array
    {
        return $this->withinTeam(
            $branchId,
            fn (): array => $this->idsWithPermission(
                User::query()
                    ->where('user_type', UserType::BRANCH)
                    ->where('branch_id', $branchId),
                $permission,
            ),
        );
    }

    /**
     * @param  Builder<User>  $query
     * @return list<int>
     */
    private function idsWithPermission($query, PermissionEnum|string $permission): array
    {
        $ability = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return $query
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $user): bool => $user->can($ability))
            ->map(fn (User $user): int => (int) $user->id)
            ->values()
            ->all();
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function withinTeam(int $teamId, Closure $callback): mixed
    {
        $previous = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId($teamId);

        try {
            return $callback();
        } finally {
            $this->registrar->setPermissionsTeamId($previous);
        }
    }
}
