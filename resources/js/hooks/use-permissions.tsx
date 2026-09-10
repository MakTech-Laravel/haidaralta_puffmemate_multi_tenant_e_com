import { usePage } from '@inertiajs/react';
import type { PermissionKey } from '@/types/permissions';

export function usePermission() {
    const { auth } = usePage().props;
    const user = auth.user;
    const roles = user?.roles ?? [];
    const permissions = user?.permissions ?? [];
    const isSuperAdmin = user?.is_super_admin ?? false;

    const can = (permission: PermissionKey): boolean => {
        if (isSuperAdmin) {
            return true;
        }

        return permissions.includes(permission);
    };

    const canAny = (permissionList: PermissionKey[]): boolean => {
        if (isSuperAdmin) {
            return true;
        }

        return permissionList.some((permission) =>
            permissions.includes(permission),
        );
    };

    const canAll = (permissionList: PermissionKey[]): boolean => {
        if (isSuperAdmin) {
            return true;
        }

        return permissionList.every((permission) =>
            permissions.includes(permission),
        );
    };

    const hasRole = (role: string): boolean => roles.includes(role);

    const hasAnyRole = (roleList: string[]): boolean =>
        roleList.some((role) => roles.includes(role));

    return {
        can,
        canAny,
        canAll,
        hasRole,
        hasAnyRole,
        roles,
        permissions,
        isSuperAdmin,
    };
}
