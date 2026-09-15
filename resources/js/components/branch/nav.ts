import {
    Boxes,
    CalendarClock,
    ChartColumn,
    Inbox,
    KeyRound,
    LayoutGrid,
    MapPin,
    MapPinned,
    MessageCircle,
    MessagesSquare,
    Package,
    Package2,
    ScrollText,
    Shield,
    ShoppingBag,
    Star,
    Store,
    Tags,
    Truck,
    Users,
} from 'lucide-react';
import {
    navGroup,
    navLink,
    navSection,
    navSoon,
} from '@/components/navigation/nav-builders';
import type { NavSection } from '@/components/navigation/types';

export function buildBranchNav(slug: string): NavSection[] {
    return [
        navSection(undefined, [
            navLink({
                title: 'Dashboard',
                href: `/branch/${slug}/dashboard`,
                icon: LayoutGrid,
                permissions: ['view-branch-dashboard'],
            }),
            navGroup({
                title: 'Catalog',
                href: `/branch/${slug}/products`,
                icon: Package,
                permissions: ['manage-branch-products'],
                items: [
                    navLink({
                        title: 'Categories',
                        href: `/branch/${slug}/categories`,
                        icon: Tags,
                        permissions: ['manage-branch-products'],
                    }),
                    navLink({
                        title: 'Products',
                        href: `/branch/${slug}/products`,
                        icon: Package,
                        permissions: ['manage-branch-products'],
                    }),
                ],
            }),
            navGroup({
                title: 'Orders',
                icon: ShoppingBag,
                permissions: ['manage-branch-orders'],
                items: [
                    navSoon({
                        title: 'All orders',
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
                        title: 'Shifts',
                        icon: CalendarClock,
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
                title: 'Customers',
                icon: Users,
                permissions: ['manage-branch-orders'],
                items: [
                    navSoon({
                        title: 'All customers',
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
                title: 'Conversations',
                icon: MessagesSquare,
                permissions: ['manage-branch-orders', 'manage-branch-drivers'],
                items: [
                    navSoon({
                        title: 'Support inbox',
                        icon: Inbox,
                        permissions: ['manage-branch-orders'],
                    }),
                    navSoon({
                        title: 'Driver chat',
                        icon: MessageCircle,
                        permissions: ['manage-branch-drivers'],
                    }),
                ],
            }),
            navGroup({
                title: 'Reports',
                icon: ChartColumn,
                permissions: ['manage-branch-orders'],
                items: [
                    navSoon({
                        title: 'Sales',
                        icon: ChartColumn,
                        permissions: ['manage-branch-orders'],
                    }),
                    navSoon({
                        title: 'Inventory',
                        icon: Boxes,
                        permissions: ['manage-branch-orders'],
                    }),
                ],
            }),
            navGroup({
                title: 'Administrator',
                icon: Shield,
                permissions: ['manage-branch-staff', 'manage-branch-roles'],
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
                ],
            }),
            navLink({
                title: 'Audit logs',
                href: `/branch/${slug}/activity-logs`,
                icon: ScrollText,
                permissions: ['view-branch-activity-logs'],
            }),
        ]),
    ];
}
