import {
    Activity,
    Building2,
    BellRing,
    Database,
    Gauge,
    KeyRound,
    LayoutGrid,
    Package,
    Radio,
    ScrollText,
    Shield,
    Tags,
    UserCog,
    Users,
    Truck,
} from 'lucide-react';
import { navGroup, navLink } from '@/components/navigation/nav-builders';
import type { NavNode } from '@/components/navigation/types';

export const platformNav: NavNode[] = [
    navLink({
        title: 'Dashboard',
        href: '/platform/dashboard',
        icon: LayoutGrid,
        permissions: ['view-dashboard'],
    }),
    navLink({
        title: 'Branches',
        href: '/platform/branches',
        icon: Building2,
        permissions: ['view-branches'],
    }),
    navLink({
        title: 'Drivers',
        href: '/platform/drivers',
        icon: Truck,
        permissions: ['manage-drivers'],
    }),
    navGroup({
        title: 'Catalog',
        href: '/platform/categories',
        icon: Package,
        permissions: ['manage-catalog'],
        items: [
            navLink({
                title: 'Categories',
                href: '/platform/categories',
                icon: Tags,
                permissions: ['manage-catalog'],
            }),
            navLink({
                title: 'Products',
                href: '/platform/products',
                icon: Package,
                permissions: ['manage-catalog'],
            }),
        ],
    }),
    navLink({
        title: 'Activity log',
        href: '/platform/activity-logs',
        icon: ScrollText,
        permissions: ['view-activity-logs'],
    }),
    navLink({
        title: 'Database backups',
        href: '/platform/database-backups',
        icon: Database,
        permissions: ['manage-database-backups'],
    }),
    navGroup({
        title: 'Observability',
        href: '/platform/observability',
        icon: Gauge,
        permissions: ['view-horizon', 'view-pulse'],
        items: [
            navLink({
                title: 'Hub',
                href: '/platform/observability',
                permissions: ['view-horizon', 'view-pulse'],
            }),
            navLink({
                title: 'Horizon',
                href: '/horizon',
                icon: Activity,
                external: true,
                permissions: ['view-horizon'],
            }),
            navLink({
                title: 'Pulse',
                href: '/pulse',
                icon: Gauge,
                external: true,
                permissions: ['view-pulse'],
            }),
        ],
    }),
    navLink({
        title: 'Realtime Console',
        href: '/platform/realtime',
        icon: Radio,
        permissions: ['view-dashboard'],
    }),
    navLink({
        title: 'FCM Console',
        href: '/platform/fcm',
        icon: BellRing,
        permissions: ['view-dashboard'],
    }),
    navLink({
        title: 'Roles',
        href: '/platform/roles',
        icon: Shield,
        permissions: ['manage-roles'],
    }),
    navLink({
        title: 'Permissions',
        href: '/platform/permissions',
        icon: KeyRound,
        permissions: ['manage-roles'],
    }),
    navLink({
        title: 'Staff',
        href: '/platform/staff',
        icon: UserCog,
        permissions: ['manage-users'],
    }),
    navLink({
        title: 'Branch staff',
        href: '/platform/branch-staff',
        icon: Users,
        permissions: ['manage-branch-users'],
    }),
];
