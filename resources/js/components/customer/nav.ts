import { LayoutGrid, ScrollText } from 'lucide-react';
import type { NavNode } from '@/components/navigation/types';

export const customerNav: NavNode[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My activity',
        href: '/activity',
        icon: ScrollText,
    },
];
