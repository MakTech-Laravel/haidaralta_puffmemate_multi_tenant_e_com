import {
    Activity,
    BellRing,
    Building2,
    CalendarClock,
    ChartColumn,
    Database,
    Gauge,
    KeyRound,
    LayoutGrid,
    Mail,
    Package,
    Radio,
    ScrollText,
    Settings2,
    Shield,
    ShoppingBag,
    Tags,
    Truck,
    UserCog,
    UserPlus,
    Users,
} from 'lucide-react';
import {
    navGroup,
    navLink,
    navSection,
    navSoon,
} from '@/components/navigation/nav-builders';
import type { NavSection } from '@/components/navigation/types';

export const platformNav: NavSection[] = [
    navSection(undefined, [
        navLink({
            title: 'Dashboard',
            href: '/platform/dashboard',
            icon: LayoutGrid,
            permissions: ['view-dashboard'],
        }),
        navGroup({
            title: 'Administrator',
            icon: Shield,
            permissions: ['manage-users', 'manage-roles'],
            items: [
                navLink({
                    title: 'Admins',
                    href: '/platform/staff',
                    icon: UserCog,
                    permissions: ['manage-users'],
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
            ],
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
        navGroup({
            title: 'Branch management',
            href: '/platform/branches',
            icon: Building2,
            permissions: [
                'view-branches',
                'manage-drivers',
                'manage-branch-users',
            ],
            items: [
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
                navLink({
                    title: 'Branch staff',
                    href: '/platform/branch-staff',
                    icon: Users,
                    permissions: ['manage-branch-users'],
                }),
            ],
        }),
        navGroup({
            title: 'Shift management',
            icon: CalendarClock,
            permissions: ['manage-drivers'],
            items: [
                navSoon({
                    title: 'Shifts',
                    icon: CalendarClock,
                    permissions: ['manage-drivers'],
                }),
                navSoon({
                    title: 'Assign to shift',
                    icon: UserPlus,
                    permissions: ['manage-drivers'],
                }),
            ],
        }),
        navGroup({
            title: 'Commerce',
            icon: ShoppingBag,
            permissions: ['manage-reports', 'manage-users'],
            items: [
                navSoon({
                    title: 'Orders',
                    icon: ShoppingBag,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Reports',
                    icon: ChartColumn,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Customers',
                    icon: Users,
                    permissions: ['manage-users'],
                }),
            ],
        }),
        navGroup({
            title: 'Application settings',
            icon: Settings2,
            permissions: [
                'manage-database-backups',
                'view-activity-logs',
                'view-horizon',
                'view-pulse',
                'view-dashboard',
            ],
            items: [
                navSoon({
                    title: 'General',
                    icon: Settings2,
                    permissions: ['manage-database-backups'],
                }),
                navLink({
                    title: 'Database',
                    href: '/platform/database-backups',
                    icon: Database,
                    permissions: ['manage-database-backups'],
                }),
                navSoon({
                    title: 'SMTP',
                    icon: Mail,
                    permissions: ['manage-database-backups'],
                }),
                navLink({
                    title: 'Audit logs',
                    href: '/platform/activity-logs',
                    icon: ScrollText,
                    permissions: ['view-activity-logs'],
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
                    title: 'Realtime console',
                    href: '/platform/realtime',
                    icon: Radio,
                    permissions: ['view-dashboard'],
                }),
                navLink({
                    title: 'FCM console',
                    href: '/platform/fcm',
                    icon: BellRing,
                    permissions: ['view-dashboard'],
                }),
            ],
        }),
    ]),
];
