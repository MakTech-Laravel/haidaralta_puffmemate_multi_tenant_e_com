<?php

namespace App\Support;

use Spatie\Permission\DefaultTeamResolver;

/**
 * Resolves the active Spatie team id for permission checks.
 *
 * The platform scope uses a reserved team id ({@see self::PLATFORM_TEAM_ID})
 * rather than NULL. PostgreSQL does not treat NULL = NULL as equal in a unique
 * constraint, so nullable branch_id on roles cannot prevent duplicate platform
 * role names. Reserved id 0 keeps the unique index enforceable.
 *
 * When no team has been set explicitly (platform/driver/customer requests,
 * seeders, tests), the platform team is assumed. ResolveBranchContext middleware
 * overrides this with the branch id on `/branch/{branch}/*` routes.
 */
class PlatformTeamResolver extends DefaultTeamResolver
{
    /** Reserved team id for platform-level roles. No branch ever has id 0. */
    public const PLATFORM_TEAM_ID = 0;

    public function getPermissionsTeamId(): int|string|null
    {
        return parent::getPermissionsTeamId() ?? self::PLATFORM_TEAM_ID;
    }
}
