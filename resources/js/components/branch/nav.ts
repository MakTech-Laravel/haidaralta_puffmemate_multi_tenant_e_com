import { LayoutGrid } from 'lucide-react';
import type { NavNode } from '@/components/navigation/types';

export function buildBranchNav(slug: string): NavNode[] {
    return [
        {
            title: 'Dashboard',
            href: `/branch/${slug}/dashboard`,
            icon: LayoutGrid,
            permissions: ['view-branch-dashboard'],
        },
    ];
}
