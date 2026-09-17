export type DriverNavItem = {
    label: string;
    href?: string;
    match?: (url: string) => boolean;
    logout?: boolean;
};

/** Text sidebar items for the driver account-style shell (not PortalShell / bottom tabs). */
export const driverNavItems: DriverNavItem[] = [
    {
        label: 'Dashboard',
        href: '/driver/dashboard',
        match: (url) =>
            url === '/driver/dashboard' || url.startsWith('/driver/dashboard?'),
    },
    {
        label: 'Orders',
        href: '/driver/orders',
        match: (url) =>
            url === '/driver/orders' ||
            url.startsWith('/driver/orders?') ||
            url.startsWith('/driver/orders/'),
    },
    {
        label: 'My activity',
        href: '/driver/activity',
        match: (url) =>
            url === '/driver/activity' ||
            url.startsWith('/driver/activity?') ||
            url.startsWith('/driver/activity/'),
    },
    {
        label: 'Log Out',
        logout: true,
    },
];
