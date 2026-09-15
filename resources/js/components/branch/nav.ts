import {
    KeyRound,
    LayoutGrid,
    Package,
    ScrollText,
    Shield,
    Tags,
    Truck,
    Users,
} from 'lucide-react';
import { navGroup, navLink } from '@/components/navigation/nav-builders';
import type { NavNode } from '@/components/navigation/types';

export function buildBranchNav(slug: string): NavNode[] {
    return [
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
        navLink({
            title: 'Activity log',
            href: `/branch/${slug}/activity-logs`,
            icon: ScrollText,
            permissions: ['view-branch-activity-logs'],
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
            title: 'Staff',
            href: `/branch/${slug}/staff`,
            icon: Users,
            permissions: ['manage-branch-staff'],
        }),
        navLink({
            title: 'Drivers',
            href: `/branch/${slug}/drivers`,
            icon: Truck,
            permissions: ['manage-branch-drivers'],
        }),
    ];
}
