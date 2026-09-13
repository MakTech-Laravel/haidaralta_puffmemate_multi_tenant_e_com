import { KeyRound, LayoutGrid, Package, ScrollText, Shield, Users } from 'lucide-react';
import type { NavNode } from '@/components/navigation/types';

export function buildBranchNav(slug: string): NavNode[] {
    return [
        {
            title: 'Dashboard',
            href: `/branch/${slug}/dashboard`,
            icon: LayoutGrid,
            permissions: ['view-branch-dashboard'],
        },
        {
            title: 'Products',
            href: `/branch/${slug}/products`,
            icon: Package,
            permissions: ['manage-branch-products'],
        },
        {
            title: 'Activity log',
            href: `/branch/${slug}/activity-logs`,
            icon: ScrollText,
            permissions: ['view-branch-activity-logs'],
        },
        {
            title: 'Roles',
            href: `/branch/${slug}/roles`,
            icon: Shield,
            permissions: ['manage-branch-roles'],
        },
        {
            title: 'Permissions',
            href: `/branch/${slug}/permissions`,
            icon: KeyRound,
            permissions: ['manage-branch-roles'],
        },
        {
            title: 'Staff',
            href: `/branch/${slug}/staff`,
            icon: Users,
            permissions: ['manage-branch-staff'],
        },
    ];
}
