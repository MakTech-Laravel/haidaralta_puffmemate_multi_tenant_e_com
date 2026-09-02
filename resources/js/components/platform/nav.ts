import { LayoutGrid } from 'lucide-react';
import type { NavNode } from '@/components/navigation/types';

export const platformNav: NavNode[] = [
    {
        title: 'Dashboard',
        href: '/platform/dashboard',
        icon: LayoutGrid,
        permissions: ['view-dashboard'],
    },
];
