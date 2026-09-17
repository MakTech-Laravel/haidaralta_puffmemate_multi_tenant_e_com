import {
    Activity,
    BadgePercent,
    Banknote,
    Bell,
    BellRing,
    Boxes,
    Building2,
    CalendarClock,
    ChartColumn,
    CreditCard,
    Database,
    Gauge,
    IdCard,
    Inbox,
    KeyRound,
    LayoutGrid,
    Mail,
    MapPin,
    MapPinned,
    Megaphone,
    MessageCircle,
    MessagesSquare,
    Package,
    Package2,
    PackagePlus,
    Radio,
    ScrollText,
    Settings2,
    Shield,
    ShoppingBag,
    Star,
    Store,
    Tags,
    Truck,
    UserCog,
    UserPlus,
    Users,
    Warehouse,
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
            title: 'Stock management',
            href: '/platform/stock/manage',
            icon: Boxes,
            permissions: ['manage-branch-inventories'],
            items: [
                navLink({
                    title: 'Manage',
                    href: '/platform/stock/manage',
                    icon: Settings2,
                    permissions: ['manage-branch-inventories'],
                }),
                navLink({
                    title: 'Bulk Import',
                    href: '/platform/stock/import',
                    icon: PackagePlus,
                    permissions: ['manage-branch-inventories'],
                }),
                navLink({
                    title: 'Transactions',
                    href: '/platform/stock/transactions',
                    icon: ScrollText,
                    permissions: ['manage-branch-inventories'],
                }),
            ],
        }),
        navGroup({
            title: 'Orders',
            icon: ShoppingBag,
            permissions: ['manage-reports'],
            items: [
                navSoon({
                    title: 'Orders',
                    icon: ShoppingBag,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Pickup',
                    icon: Store,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Delivery',
                    icon: MapPin,
                    permissions: ['manage-reports'],
                }),
            ],
        }),
        navGroup({
            title: 'Customers',
            icon: Users,
            permissions: ['manage-users'],
            items: [
                navSoon({
                    title: 'Customers',
                    icon: Users,
                    permissions: ['manage-users'],
                }),
                navSoon({
                    title: 'Reviews',
                    icon: Star,
                    permissions: ['manage-users'],
                }),
                navSoon({
                    title: 'Wholesale',
                    icon: Warehouse,
                    permissions: ['manage-users'],
                }),
                navSoon({
                    title: 'Age verification',
                    icon: IdCard,
                    permissions: ['manage-users'],
                }),
            ],
        }),
        navGroup({
            title: 'Branch management',
            href: '/platform/branches',
            icon: Building2,
            permissions: ['view-branches', 'manage-branch-users'],
            items: [
                navLink({
                    title: 'Branches',
                    href: '/platform/branches',
                    icon: Building2,
                    permissions: ['view-branches'],
                }),
                navLink({
                    title: 'Staff',
                    href: '/platform/branch-staff',
                    icon: Users,
                    permissions: ['manage-branch-users'],
                }),
                navLink({
                    title: 'Add staff',
                    href: '/platform/branch-staff/create',
                    icon: UserPlus,
                    exact: true,
                    permissions: ['manage-branch-users'],
                }),
            ],
        }),
        navGroup({
            title: 'Driver management',
            href: '/platform/drivers',
            icon: Truck,
            permissions: ['manage-drivers'],
            items: [
                navLink({
                    title: 'Drivers',
                    href: '/platform/drivers',
                    icon: Truck,
                    permissions: ['manage-drivers'],
                }),
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
                navSoon({
                    title: 'Live tracking',
                    icon: MapPinned,
                    permissions: ['manage-drivers'],
                }),
                navSoon({
                    title: 'Parcels',
                    icon: Package2,
                    permissions: ['manage-drivers'],
                }),
            ],
        }),
        navGroup({
            title: 'Conversations',
            icon: MessagesSquare,
            permissions: ['manage-users', 'manage-drivers'],
            items: [
                navSoon({
                    title: 'Support inbox',
                    icon: Inbox,
                    permissions: ['manage-users'],
                }),
                navSoon({
                    title: 'Driver chat',
                    icon: MessageCircle,
                    permissions: ['manage-drivers'],
                }),
            ],
        }),
        navGroup({
            title: 'Marketing',
            icon: Megaphone,
            permissions: ['manage-reports'],
            items: [
                navSoon({
                    title: 'Coupons',
                    icon: BadgePercent,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Deals',
                    icon: Megaphone,
                    permissions: ['manage-reports'],
                }),
            ],
        }),
        navGroup({
            title: 'Reports',
            icon: ChartColumn,
            permissions: ['manage-reports'],
            items: [
                navSoon({
                    title: 'Sales',
                    icon: Banknote,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Inventory',
                    icon: Boxes,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Drivers',
                    icon: Truck,
                    permissions: ['manage-reports'],
                }),
                navSoon({
                    title: 'Customers',
                    icon: Users,
                    permissions: ['manage-reports'],
                }),
            ],
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
                    title: 'Notifications',
                    href: '/platform/notifications',
                    icon: Bell,
                    permissions: ['view-dashboard'],
                }),
                navSoon({
                    title: 'Payments',
                    icon: CreditCard,
                    permissions: ['manage-database-backups'],
                }),
                navSoon({
                    title: 'Delivery and fees',
                    icon: Truck,
                    permissions: ['manage-database-backups'],
                }),
                navSoon({
                    title: 'SMTP',
                    icon: Mail,
                    permissions: ['manage-database-backups'],
                }),
                navLink({
                    title: 'Backups',
                    href: '/platform/database-backups',
                    icon: Database,
                    permissions: ['manage-database-backups'],
                }),
                navLink({
                    title: 'Audit logs',
                    href: '/platform/activity-logs',
                    icon: ScrollText,
                    permissions: ['view-activity-logs'],
                }),
                navGroup({
                    title: 'Monitoring',
                    href: '/platform/observability',
                    icon: Gauge,
                    permissions: ['view-horizon', 'view-pulse'],
                    items: [
                        navLink({
                            title: 'Overview',
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
