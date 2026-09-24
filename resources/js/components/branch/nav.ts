import {
    Bell,
    Boxes,
    CalendarClock,
    ChartColumn,
    CircleDollarSign,
    Clock,
    History,
    Inbox,
    KeyRound,
    LayoutGrid,
    MapPin,
    MapPinned,
    MessageCircle,
    MessagesSquare,
    Package,
    Package2,
    PackagePlus,
    ScrollText,
    Settings2,
    Shield,
    ShoppingBag,
    Star,
    Store,
    Tags,
    Truck,
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

export function buildBranchNav(
    slug: string,
    canViewCategories = false,
    showBulkImport = false,
    showBranchPricing = false,
    branchCanManageCatalogPricing = true,
): NavSection[] {
    const productItems = [
        ...(canViewCategories
            ? [
                  navLink({
                      title: 'Categories',
                      href: `/branch/${slug}/categories`,
                      icon: Tags,
                      permissions: ['manage-branch-products'],
                  }),
              ]
            : []),
        navLink({
            title: 'Products',
            href: `/branch/${slug}/products`,
            icon: Package,
            permissions: ['manage-branch-products'],
        }),
        ...(!showBranchPricing && branchCanManageCatalogPricing
            ? [
                  navLink({
                      title: 'Manage pricing',
                      href: `/branch/${slug}/catalog/pricing/manage`,
                      icon: CircleDollarSign,
                      permissions: ['manage-branch-products'],
                  }),
              ]
            : []),
        navLink({
            title: 'Manage stock',
            href: `/branch/${slug}/stock/manage`,
            icon: Boxes,
            permissions: ['manage-branch-products'],
        }),
        ...(showBulkImport
            ? [
                  navLink({
                      title: 'Bulk Import',
                      href: `/branch/${slug}/stock/import`,
                      icon: PackagePlus,
                      permissions: ['manage-branch-products'],
                  }),
              ]
            : []),
    ];

    return [
        navSection(undefined, [
            navLink({
                title: 'Dashboard',
                href: `/branch/${slug}/dashboard`,
                icon: LayoutGrid,
                permissions: ['view-branch-dashboard'],
            }),
            navGroup({
                title: 'Staff management',
                icon: Shield,
                permissions: [
                    'manage-branch-staff',
                    'manage-branch-roles',
                    'manage-branch-shifts',
                ],
                items: [
                    navLink({
                        title: 'Staff',
                        href: `/branch/${slug}/staff`,
                        icon: Users,
                        permissions: ['manage-branch-staff'],
                    }),
                    navLink({
                        title: 'Roles',
                        href: `/branch/${slug}/roles`,
                        icon: Shield,
                        permissions: ['manage-branch-roles'],
                    }),
                    navLink({
                        title: 'Permissions',
                        href: `/branch/${slug}/permissions`,
                        icon: KeyRound,
                        permissions: ['manage-branch-roles'],
                    }),
                    navLink({
                        title: 'Shifts',
                        href: `/branch/${slug}/shifts`,
                        icon: CalendarClock,
                        permissions: ['manage-branch-shifts'],
                    }),
                    navLink({
                        title: 'Assign to shift',
                        href: `/branch/${slug}/shift-assignments`,
                        icon: UserPlus,
                        permissions: ['manage-branch-shifts'],
                    }),
                ],
            }),
            navGroup({
                title: 'Customer management',
                icon: Users,
                permissions: ['manage-branch-orders'],
                items: [
                    navSoon({
                        title: 'Customers',
                        icon: Users,
                        permissions: ['manage-branch-orders'],
                    }),
                    navSoon({
                        title: 'Reviews',
                        icon: Star,
                        permissions: ['manage-branch-orders'],
                    }),
                ],
            }),
            navGroup({
                title: 'Driver management',
                href: `/branch/${slug}/drivers`,
                icon: Truck,
                permissions: ['manage-branch-drivers'],
                items: [
                    navLink({
                        title: 'Drivers',
                        href: `/branch/${slug}/drivers`,
                        icon: Truck,
                        permissions: ['manage-branch-drivers'],
                    }),
                    navSoon({
                        title: 'Live tracking',
                        icon: MapPinned,
                        permissions: ['manage-branch-drivers'],
                    }),
                    navSoon({
                        title: 'Parcels',
                        icon: Package2,
                        permissions: ['manage-branch-drivers'],
                    }),
                ],
            }),
            navGroup({
                title: 'Product management',
                href: `/branch/${slug}/products`,
                icon: Package,
                permissions: ['manage-branch-products'],
                items: productItems,
            }),
            navGroup({
                title: 'Order management',
                icon: ShoppingBag,
                permissions: ['manage-branch-orders'],
                items: [
                    navSoon({
                        title: 'Orders',
                        icon: ShoppingBag,
                        permissions: ['manage-branch-orders'],
                    }),
                    navSoon({
                        title: 'Pickup',
                        icon: Store,
                        permissions: ['manage-branch-orders'],
                    }),
                    navSoon({
                        title: 'Delivery',
                        icon: MapPin,
                        permissions: ['manage-branch-orders'],
                    }),
                ],
            }),
            navGroup({
                title: 'Conversations',
                href: `/branch/${slug}/conversations`,
                icon: MessagesSquare,
                permissions: [
                    'manage-branch-conversations',
                    'manage-branch-drivers',
                ],
                items: [
                    navLink({
                        title: 'Order chats',
                        href: `/branch/${slug}/conversations`,
                        icon: Inbox,
                        permissions: ['manage-branch-conversations'],
                    }),
                    navSoon({
                        title: 'Driver chat',
                        icon: MessageCircle,
                        permissions: ['manage-branch-drivers'],
                    }),
                ],
            }),
            navGroup({
                title: 'Reports and history',
                icon: ChartColumn,
                permissions: ['manage-branch-orders', 'manage-branch-products'],
                items: [
                    navSoon({
                        title: 'Sales',
                        icon: ChartColumn,
                        permissions: ['manage-branch-orders'],
                    }),
                    navLink({
                        title: 'Stock transactions',
                        href: `/branch/${slug}/reports/stock-transactions`,
                        icon: ScrollText,
                        permissions: ['manage-branch-products'],
                    }),
                    ...(showBranchPricing
                        ? [
                              navLink({
                                  title: 'Price history',
                                  href: `/branch/${slug}/reports/price-history`,
                                  icon: History,
                                  permissions: ['manage-branch-products'],
                              }),
                          ]
                        : []),
                ],
            }),
            navGroup({
                title: 'Settings',
                icon: Settings2,
                permissions: [
                    'view-branch-activity-logs',
                    'view-branch-dashboard',
                    'manage-branch-settings',
                ],
                items: [
                    navLink({
                        title: 'General',
                        href: `/branch/${slug}/settings/general`,
                        icon: Settings2,
                        permissions: ['manage-branch-settings'],
                    }),
                    navSoon({
                        title: 'Hours',
                        icon: Clock,
                        permissions: ['view-branch-activity-logs'],
                    }),
                    navLink({
                        title: 'Notifications',
                        href: `/branch/${slug}/notifications`,
                        icon: Bell,
                        permissions: ['view-branch-dashboard'],
                    }),
                    navLink({
                        title: 'Audit logs',
                        href: `/branch/${slug}/activity-logs`,
                        icon: ScrollText,
                        permissions: ['view-branch-activity-logs'],
                    }),
                ],
            }),
        ]),
    ];
}
