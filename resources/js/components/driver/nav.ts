import { LayoutGrid, ScrollText } from 'lucide-react';
import type { NavNode } from '@/components/navigation/types';

export const driverNav: NavNode[] = [
    {
        title: 'Dashboard',
        href: '/driver/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My activity',
        href: '/driver/activity',
        icon: ScrollText,
    },
];
