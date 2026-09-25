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
    FileText,
    Gauge,
    History,
    IdCard,
    Images,
    Inbox,
    KeyRound,
    LayoutGrid,
    Mail,
    MapPin,
    MapPinned,
    Megaphone,
    MessageCircle,
    MessagesSquare,
    Phone,
    Package,
    Package2,
    PackagePlus,
    CircleDollarSign,
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
    UserRound,
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
import { edit as profileEdit } from '@/routes/profile';

export function buildPlatformNav(
    showBulkImport = false,
    showBranchPricing = false,
    showPlatformAdministratorShifts = false,
): NavSection[] {
    return [
        navSection(undefined, [
            navLink({
                title: 'Dashboard',
                href: '/platform/dashboard',
                icon: LayoutGrid,
                permissions: ['view-dashboard'],
            }),
            navLink({
                title: 'Profile',
                href: profileEdit.url(),
                icon: UserRound,
            }),
            navGroup({
                title: 'Administrator',
                icon: Shield,
                permissions: showPlatformAdministratorShifts
                    ? ['manage-users', 'manage-roles', 'manage-shifts']
                    : ['manage-users', 'manage-roles'],
                items: [
                    navLink({
                        title: 'Admins',
                        href: '/platform/admins',
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
                    ...(showPlatformAdministratorShifts
                        ? [
                              navLink({
                                  title: 'Shifts',
                                  href: '/platform/shifts',
                                  icon: CalendarClock,
                                  permissions: ['manage-shifts'],
                              }),
                              navLink({
                                  title: 'Assign to shift',
                                  href: '/platform/shift-assignments',
                                  icon: UserPlus,
                                  permissions: ['manage-shifts'],
                              }),
                          ]
                        : []),
                ],
            }),
            navGroup({
                title: 'Customer management',
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
                title: 'Branch management',
                href: '/platform/branches',
                icon: Building2,
                permissions: [
                    'view-branches',
                    'manage-branch-users',
                    'manage-branch-inventories',
                    'manage-shifts',
                ],
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
                        title: 'Shifts',
                        href: '/platform/branch-shifts',
                        icon: CalendarClock,
                        permissions: ['manage-shifts'],
                    }),
                    navLink({
                        title: 'Assign to shift',
                        href: '/platform/branch-shift-assignments',
                        icon: UserPlus,
                        permissions: ['manage-shifts'],
                    }),
                    navLink({
                        title: 'Manage stock',
                        href: '/platform/stock/manage',
                        icon: Boxes,
                        permissions: ['manage-branch-inventories'],
                    }),
                ],
            }),
            navGroup({
                title: 'Product management',
                href: '/platform/products',
                icon: Package,
                permissions: showBulkImport
                    ? ['manage-catalog', 'manage-branch-inventories']
                    : ['manage-catalog'],
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
                    navLink({
                        title: 'Shop price ranges',
                        href: '/platform/price-ranges',
                        icon: CircleDollarSign,
                        permissions: ['manage-catalog'],
                    }),
                    navLink({
                        title: 'Coupons',
                        href: '/platform/coupons',
                        icon: BadgePercent,
                        permissions: ['manage-catalog'],
                    }),
                    navLink({
                        title: 'Delivery settings',
                        href: '/platform/delivery-settings',
                        icon: Truck,
                        permissions: ['manage-catalog'],
                    }),
                    ...(!showBranchPricing
                        ? [
                              navLink({
                                  title: 'Manage pricing',
                                  href: '/platform/catalog/pricing/manage',
                                  icon: CircleDollarSign,
                                  permissions: ['manage-catalog'],
                              }),
                          ]
                        : []),
                    ...(showBulkImport
                        ? [
                              navLink({
                                  title: 'Bulk Import',
                                  href: '/platform/stock/import',
                                  icon: PackagePlus,
                                  permissions: ['manage-branch-inventories'],
                              }),
                          ]
                        : []),
                ],
            }),
            navGroup({
                title: 'Order management',
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
                title: 'Conversations',
                href: '/platform/conversations',
                icon: MessagesSquare,
                permissions: ['manage-conversations', 'manage-drivers'],
                items: [
                    navLink({
                        title: 'Order chats',
                        href: '/platform/conversations',
                        icon: Inbox,
                        permissions: ['manage-conversations'],
                    }),
                    navSoon({
                        title: 'Driver chat',
                        icon: MessageCircle,
                        permissions: ['manage-drivers'],
                    }),
                ],
            }),
            navGroup({
                title: 'CMS',
                icon: FileText,
                permissions: ['manage-pages'],
                items: [
                    navLink({
                        title: 'All pages',
                        href: '/platform/pages',
                        icon: FileText,
                        permissions: ['manage-pages'],
                    }),
                    navLink({
                        title: 'Home banners',
                        href: '/platform/home-banners',
                        icon: Images,
                        permissions: ['manage-pages'],
                    }),
                ],
            }),
            navGroup({
                title: 'Marketing',
                icon: Megaphone,
                permissions: ['manage-reports', 'manage-pages'],
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
                title: 'Reports and history',
                icon: ChartColumn,
                permissions: [
                    'manage-reports',
                    'manage-branch-inventories',
                    'view-transfers',
                ],
                items: [
                    navSoon({
                        title: 'Sales',
                        icon: Banknote,
                        permissions: ['manage-reports'],
                    }),
                    navLink({
                        title: 'Stock transactions',
                        href: '/platform/reports/stock-transactions',
                        icon: ScrollText,
                        permissions: ['manage-branch-inventories'],
                    }),
                    ...(showBranchPricing
                        ? [
                              navLink({
                                  title: 'Price history',
                                  href: '/platform/reports/price-history',
                                  icon: History,
                                  permissions: ['manage-branch-inventories'],
                              }),
                          ]
                        : []),
                    navLink({
                        title: 'Transfer history',
                        href: '/platform/reports/transfer-history',
                        icon: History,
                        permissions: ['view-transfers'],
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
                title: 'Application settings',
                icon: Settings2,
                permissions: [
                    'manage-application-settings',
                    'manage-database-backups',
                    'view-activity-logs',
                    'view-horizon',
                    'view-pulse',
                    'view-dashboard',
                ],
                items: [
                    navLink({
                        title: 'General',
                        href: '/platform/settings/general',
                        icon: Settings2,
                        permissions: ['manage-application-settings'],
                    }),
                    navLink({
                        title: 'Contacts',
                        href: '/platform/settings/contacts',
                        icon: Phone,
                        permissions: ['manage-application-settings'],
                    }),
                    navLink({
                        title: 'Notifications',
                        href: '/platform/notifications',
                        icon: Bell,
                        permissions: ['view-dashboard'],
                    }),
                    navLink({
                        title: 'Payment Gateway',
                        href: '/platform/settings/payment-gateway',
                        icon: CreditCard,
                        permissions: ['manage-application-settings'],
                    }),
                    navLink({
                        title: 'Payment methods',
                        href: '/platform/payment-methods',
                        icon: Banknote,
                        permissions: ['manage-catalog'],
                    }),
                    navLink({
                        title: 'SMTP',
                        href: '/platform/settings/smtp',
                        icon: Mail,
                        permissions: ['manage-application-settings'],
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
}
