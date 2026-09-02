import { LayoutGrid, Users } from 'lucide-react';
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
            title: 'Staff',
            href: `/branch/${slug}/staff`,
            icon: Users,
            permissions: ['manage-branch-staff'],
        },
    ];
}
