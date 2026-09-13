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
} from 'lucide-react';
import type { NavNode } from '@/components/navigation/types';

export const platformNav: NavNode[] = [
    {
        title: 'Dashboard',
        href: '/platform/dashboard',
        icon: LayoutGrid,
        permissions: ['view-dashboard'],
    },
    {
        title: 'Branches',
        href: '/platform/branches',
        icon: Building2,
        permissions: ['view-branches'],
    },
    {
        title: 'Catalog',
        href: '/platform/categories',
        icon: Package,
        permissions: ['manage-catalog'],
        items: [
            {
                title: 'Categories',
                href: '/platform/categories',
                icon: Tags,
                permissions: ['manage-catalog'],
            },
            {
                title: 'Products',
                href: '/platform/products',
                icon: Package,
                permissions: ['manage-catalog'],
            },
        ],
    },
    {
        title: 'Activity log',
        href: '/platform/activity-logs',
        icon: ScrollText,
        permissions: ['view-activity-logs'],
    },
    {
        title: 'Database backups',
        href: '/platform/database-backups',
        icon: Database,
        permissions: ['manage-database-backups'],
    },
    {
        title: 'Observability',
        href: '/platform/observability',
        icon: Gauge,
        permissions: ['view-horizon', 'view-pulse'],
        items: [
            {
                title: 'Hub',
                href: '/platform/observability',
                permissions: ['view-horizon', 'view-pulse'],
            },
            {
                title: 'Horizon',
                href: '/horizon',
                icon: Activity,
                external: true,
                permissions: ['view-horizon'],
            },
            {
                title: 'Pulse',
                href: '/pulse',
                icon: Gauge,
                external: true,
                permissions: ['view-pulse'],
            },
        ],
    },
    {
        title: 'Realtime Console',
        href: '/platform/realtime',
        icon: Radio,
        permissions: ['view-dashboard'],
    },
    {
        title: 'FCM Console',
        href: '/platform/fcm',
        icon: BellRing,
        permissions: ['view-dashboard'],
    },
    {
        title: 'Roles',
        href: '/platform/roles',
        icon: Shield,
        permissions: ['manage-roles'],
    },
    {
        title: 'Permissions',
        href: '/platform/permissions',
        icon: KeyRound,
        permissions: ['manage-roles'],
    },
    {
        title: 'Staff',
        href: '/platform/staff',
        icon: UserCog,
        permissions: ['manage-users'],
    },
    {
        title: 'Branch staff',
        href: '/platform/branch-staff',
        icon: Users,
        permissions: ['manage-branch-users'],
    },
];
