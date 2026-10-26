<?php

use App\Enums\PermissionEnum;
use App\Models\Branch;
use App\Models\User;
use App\Support\BranchPermissionProbe;
use Database\Seeders\BranchSeeder;

/**
 * @param  list<PermissionEnum>|null  $onlyPermissions
 */
function branchPermissionProbeUser(Branch $branch, PermissionEnum $permission, ?array $onlyPermissions = null): User
{
    return BranchPermissionProbe::ensureUser($branch, $permission, $onlyPermissions);
}

function branchPermissionControlRouteName(PermissionEnum $permission): string
{
    return $permission === PermissionEnum::MANAGE_BRANCH_ROLES
        ? 'branch.customers.index'
        : 'branch.permissions.index';
}

function branchProbeBranch(): Branch
{
    return Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
}
