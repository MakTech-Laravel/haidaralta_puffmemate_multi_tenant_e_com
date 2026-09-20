import {
    Activity,
    LayoutDashboard,
    LogOut,
    Navigation,
    Package,
    UserRound,
    type LucideIcon,
} from 'lucide-react';
import { dashboard } from '@/routes/driver';
import { index as accountIndex } from '@/routes/driver/account';
import { index as activityIndex } from '@/routes/driver/activity';
import { index as ordersIndex } from '@/routes/driver/orders';
import { index as routeIndex } from '@/routes/driver/route';

export type DriverNavItem = {
    id: string;
    label: string;
    icon: LucideIcon;
    href?: string;
    match: (url: string) => boolean;
    logout?: boolean;
    /** Shown in the phone/tablet tab bar. Desktop sidebar lists every item. */
    tab?: boolean;
    /** Short label for the tab bar when `label` is too long for five columns. */
    tabLabel?: string;
};

function matchesPath(url: string, path: string, nested = false): boolean {
    if (url === path || url.startsWith(`${path}?`)) {
        return true;
    }

    return nested && url.startsWith(`${path}/`);
}

/**
 * Five field tabs. Notifications live on the header bell, not the tab bar.
 * Route stays center; chat stays on the order.
 */
export const driverNavItems: DriverNavItem[] = [
    {
        id: 'dashboard',
        label: 'Dashboard',
        icon: LayoutDashboard,
        href: dashboard.url(),
        tab: true,
        match: (url) => matchesPath(url, dashboard.url()),
    },
    {
        id: 'orders',
        label: 'Orders',
        icon: Package,
        href: ordersIndex.url(),
        tab: true,
        match: (url) => matchesPath(url, ordersIndex.url(), true),
    },
    {
        id: 'route',
        label: 'Route',
        icon: Navigation,
        href: routeIndex.url(),
        tab: true,
        match: (url) => matchesPath(url, routeIndex.url(), true),
    },
    {
        id: 'activity',
        label: 'Activity',
        icon: Activity,
        href: activityIndex.url(),
        tab: true,
        match: (url) => matchesPath(url, activityIndex.url(), true),
    },
    {
        id: 'account',
        label: 'Account',
        icon: UserRound,
        href: accountIndex.url(),
        tab: true,
        match: (url) => matchesPath(url, accountIndex.url(), true),
    },
    {
        id: 'logout',
        label: 'Log Out',
        icon: LogOut,
        logout: true,
        match: () => false,
    },
];

export const driverTabItems = driverNavItems.filter((item) => item.tab);
