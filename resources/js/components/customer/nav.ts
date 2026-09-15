import { LayoutGrid, ScrollText } from 'lucide-react';
import { navLink } from '@/components/navigation/nav-builders';
import type { NavNode } from '@/components/navigation/types';

/**
 * Legacy portal NavNode tree. Customer pages use storefront chrome +
 * `AccountSidebar` (`account-sidebar.tsx`) instead. Kept for reference /
 * My activity route parity.
 */
export const customerNav: NavNode[] = [
    navLink({
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    }),
    navLink({
        title: 'My activity',
        href: '/activity',
        icon: ScrollText,
    }),
];
