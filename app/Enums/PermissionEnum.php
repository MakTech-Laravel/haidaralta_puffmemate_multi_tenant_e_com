<?php

namespace App\Enums;

enum PermissionEnum: string
{
    case VIEW_DASHBOARD = 'view-dashboard';
    case MANAGE_USERS = 'manage-users';
    case MANAGE_BRANCH_USERS = 'manage-branch-users';
    case MANAGE_ROLES = 'manage-roles';
    case MANAGE_BRANCHES = 'manage-branches';
    case MANAGE_REPORTS = 'manage-reports';
    case VIEW_ACTIVITY_LOGS = 'view-activity-logs';
    case MANAGE_DATABASE_BACKUPS = 'manage-database-backups';
    case VIEW_HORIZON = 'view-horizon';
    case VIEW_PULSE = 'view-pulse';
    case VIEW_BRANCHES = 'view-branches';
    case CREATE_BRANCHES = 'create-branches';
    case EDIT_BRANCHES = 'edit-branches';
    case DELETE_BRANCHES = 'delete-branches';
    case MANAGE_CATALOG = 'manage-catalog';
    case MANAGE_BRANCH_INVENTORIES = 'manage-branch-inventories';
    case MANAGE_DRIVERS = 'manage-drivers';

    case VIEW_BRANCH_DASHBOARD = 'view-branch-dashboard';
    case MANAGE_BRANCH_PRODUCTS = 'manage-branch-products';
    case MANAGE_BRANCH_ORDERS = 'manage-branch-orders';
    case MANAGE_BRANCH_STAFF = 'manage-branch-staff';
    case MANAGE_BRANCH_ROLES = 'manage-branch-roles';
    case VIEW_BRANCH_ACTIVITY_LOGS = 'view-branch-activity-logs';
    case MANAGE_BRANCH_DRIVERS = 'manage-branch-drivers';

    /**
     * The auth guard this permission belongs to.
     */
    public function guard(): string
    {
        return match ($this) {
            self::VIEW_DASHBOARD,
            self::MANAGE_USERS,
            self::MANAGE_BRANCH_USERS,
            self::MANAGE_ROLES,
            self::MANAGE_REPORTS,
            self::VIEW_ACTIVITY_LOGS,
            self::MANAGE_DATABASE_BACKUPS,
            self::VIEW_HORIZON,
            self::VIEW_PULSE,
            self::VIEW_BRANCHES,
            self::CREATE_BRANCHES,
            self::EDIT_BRANCHES,
            self::DELETE_BRANCHES,
            self::MANAGE_BRANCHES,
            self::MANAGE_CATALOG,
            self::MANAGE_BRANCH_INVENTORIES,
            self::MANAGE_DRIVERS => 'platform',

            self::VIEW_BRANCH_DASHBOARD,
            self::MANAGE_BRANCH_PRODUCTS,
            self::MANAGE_BRANCH_ORDERS,
            self::MANAGE_BRANCH_STAFF,
            self::MANAGE_BRANCH_ROLES,
            self::VIEW_BRANCH_ACTIVITY_LOGS,
            self::MANAGE_BRANCH_DRIVERS => 'branch',
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
            self::MANAGE_BRANCH_USERS,
            self::MANAGE_BRANCH_STAFF => 'Users',

            self::MANAGE_ROLES,
            self::MANAGE_BRANCH_ROLES => 'Roles',

            self::MANAGE_BRANCHES,
            self::VIEW_BRANCHES,
            self::CREATE_BRANCHES,
            self::EDIT_BRANCHES,
            self::DELETE_BRANCHES => 'Branches',

            self::MANAGE_BRANCH_PRODUCTS,
            self::MANAGE_CATALOG,
            self::MANAGE_BRANCH_INVENTORIES => 'Products',
            self::MANAGE_BRANCH_ORDERS => 'Orders',
            self::MANAGE_REPORTS => 'Reports',
            self::VIEW_ACTIVITY_LOGS,
            self::VIEW_BRANCH_ACTIVITY_LOGS => 'Activity',
            self::MANAGE_DATABASE_BACKUPS => 'Database',
            self::VIEW_HORIZON,
            self::VIEW_PULSE => 'Observability',
            self::MANAGE_DRIVERS,
            self::MANAGE_BRANCH_DRIVERS => 'Drivers',
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
            self::MANAGE_BRANCH_USERS => 'Manage branch users',
            self::MANAGE_ROLES => 'Manage roles',
            self::MANAGE_BRANCHES => 'Manage branches',
            self::MANAGE_REPORTS => 'Manage reports',
            self::VIEW_ACTIVITY_LOGS => 'View activity logs',
            self::MANAGE_DATABASE_BACKUPS => 'Manage database backups',
            self::VIEW_HORIZON => 'View Horizon',
            self::VIEW_PULSE => 'View Pulse',
            self::VIEW_BRANCHES => 'View branches',
            self::CREATE_BRANCHES => 'Create branches',
            self::EDIT_BRANCHES => 'Edit branches',
            self::DELETE_BRANCHES => 'Delete branches',
            self::MANAGE_CATALOG => 'Manage catalog',
            self::MANAGE_BRANCH_INVENTORIES => 'Manage branch inventories',
            self::MANAGE_DRIVERS => 'Manage drivers',
            self::VIEW_BRANCH_DASHBOARD => 'View branch dashboard',
            self::MANAGE_BRANCH_PRODUCTS => 'Manage branch products',
            self::MANAGE_BRANCH_ORDERS => 'Manage branch orders',
            self::MANAGE_BRANCH_STAFF => 'Manage branch staff',
            self::MANAGE_BRANCH_ROLES => 'Manage branch roles',
            self::VIEW_BRANCH_ACTIVITY_LOGS => 'View branch activity logs',
            self::MANAGE_BRANCH_DRIVERS => 'Manage branch drivers',
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
