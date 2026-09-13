import { LayoutGrid, ScrollText } from 'lucide-react';
import { navLink } from '@/components/navigation/nav-builders';
import type { NavNode } from '@/components/navigation/types';

export const driverNav: NavNode[] = [
    navLink({
        title: 'Dashboard',
        href: '/driver/dashboard',
        icon: LayoutGrid,
    }),
    navLink({
        title: 'My activity',
        href: '/driver/activity',
        icon: ScrollText,
    }),
];
