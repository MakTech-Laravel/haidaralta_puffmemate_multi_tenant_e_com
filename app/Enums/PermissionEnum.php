<?php

namespace App\Enums;

enum PermissionEnum: string
{
    case VIEW_DASHBOARD = 'view-dashboard';
    case MANAGE_USERS = 'manage-users';
    case MANAGE_ROLES = 'manage-roles';
    case MANAGE_BRANCHES = 'manage-branches';

    case VIEW_BRANCH_DASHBOARD = 'view-branch-dashboard';
    case MANAGE_BRANCH_PRODUCTS = 'manage-branch-products';
    case MANAGE_BRANCH_ORDERS = 'manage-branch-orders';
    case MANAGE_BRANCH_STAFF = 'manage-branch-staff';

    /**
     * The auth guard this permission belongs to.
     */
    public function guard(): string
    {
        return match ($this) {
            self::VIEW_DASHBOARD,
            self::MANAGE_USERS,
            self::MANAGE_ROLES,
            self::MANAGE_BRANCHES => 'platform',

            self::VIEW_BRANCH_DASHBOARD,
            self::MANAGE_BRANCH_PRODUCTS,
            self::MANAGE_BRANCH_ORDERS,
            self::MANAGE_BRANCH_STAFF => 'branch',
        };
    }

    /**
     * Short grouping label for admin UI (role editors, permission listings).
     */
    public function group(): string
    {
        return match ($this) {
            self::VIEW_DASHBOARD,
            self::VIEW_BRANCH_DASHBOARD => 'Dashboard',

            self::MANAGE_USERS,
            self::MANAGE_BRANCH_STAFF => 'Users',

            self::MANAGE_ROLES => 'Roles',
            self::MANAGE_BRANCHES => 'Branches',
            self::MANAGE_BRANCH_PRODUCTS => 'Products',
            self::MANAGE_BRANCH_ORDERS => 'Orders',
        };
    }

    /**
     * Human-readable label for UI. Auth still uses `value`.
     */
    public function label(): string
    {
        return match ($this) {
            self::VIEW_DASHBOARD => 'View dashboard',
            self::MANAGE_USERS => 'Manage users',
            self::MANAGE_ROLES => 'Manage roles',
            self::MANAGE_BRANCHES => 'Manage branches',
            self::VIEW_BRANCH_DASHBOARD => 'View branch dashboard',
            self::MANAGE_BRANCH_PRODUCTS => 'Manage branch products',
            self::MANAGE_BRANCH_ORDERS => 'Manage branch orders',
            self::MANAGE_BRANCH_STAFF => 'Manage branch staff',
        };
    }

    /**
     * All permission cases for the given guard.
     *
     * @return array<int, self>
     */
    public static function forGuard(string $guard): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $permission): bool => $permission->guard() === $guard,
        ));
    }
}
