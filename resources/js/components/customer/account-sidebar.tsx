import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Bell,
    Boxes,
    LayoutGrid,
    LogOut,
    ShoppingBag,
    Star,
    User,
    type LucideIcon,
} from 'lucide-react';
import { PwaInstallMenuItem } from '@/components/pwa/pwa-install-menu-item';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { index as accountIndex } from '@/routes/customer/account';
import { index as notificationsIndex } from '@/routes/customer/notifications';
import { index as productToReview } from '@/routes/customer/product-to-review';
import { index as wholesaleRequestsIndex } from '@/routes/customer/wholesale-requests';

export type AccountNavItem = {
    label: string;
    /** Short label for the phone tab bar. */
    shortLabel?: string;
    icon: LucideIcon;
    href?: string;
    match?: (url: string) => boolean;
    logout?: boolean;
    /** When false, hide from mobile bottom nav (still shown in desktop sidebar). */
    mobile?: boolean;
};

export const accountNavItems: AccountNavItem[] = [
    {
        label: 'Dashboard',
        shortLabel: 'Home',
        icon: LayoutGrid,
        href: '/dashboard',
        match: (url) => url === '/dashboard' || url.startsWith('/dashboard?'),
    },
    {
        label: 'Orders',
        icon: ShoppingBag,
        href: '/orders',
        match: (url) =>
            url === '/orders' ||
            url.startsWith('/orders?') ||
            url.startsWith('/orders/'),
    },
    {
        label: 'Wholesale requests',
        icon: Boxes,
        href: wholesaleRequestsIndex.url(),
        mobile: false,
        match: (url) =>
            url === '/wholesale-requests' ||
            url.startsWith('/wholesale-requests?') ||
            url.startsWith('/wholesale-requests/'),
    },
    {
        label: 'Product to review',
        shortLabel: 'Reviews',
        icon: Star,
        href: productToReview.url(),
        match: (url) =>
            url === '/product-to-review' ||
            url.startsWith('/product-to-review?') ||
            url.startsWith('/product-to-review/'),
    },
    {
        label: 'Notification',
        shortLabel: 'Alerts',
        icon: Bell,
        href: notificationsIndex.url(),
        match: (url) =>
            url === '/notifications' ||
            url.startsWith('/notifications?') ||
            url.startsWith('/notifications/'),
    },
    {
        label: 'My activity',
        icon: Activity,
        href: '/activity',
        mobile: false,
        match: (url) =>
            url === '/activity' ||
            url.startsWith('/activity?') ||
            url.startsWith('/activity/'),
    },
    {
        label: 'Account',
        icon: User,
        href: accountIndex.url(),
        match: (url) => url === '/account' || url.startsWith('/account?'),
    },
    {
        label: 'Log Out',
        shortLabel: 'Log out',
        icon: LogOut,
        logout: true,
    },
];

type AccountSidebarProps = {
    className?: string;
};

export function AccountSectionNav({ className }: AccountSidebarProps) {
    const { url } = usePage();

    return (
        <nav
            aria-label="Account sections"
            className={cn('border-border border-b lg:hidden', className)}
        >
            <ul className="scrollbar-none flex gap-2 overflow-x-auto px-4 py-3">
                {accountNavItems.map((item) => {
                    const active = Boolean(item.match?.(url));
                    const Icon = item.icon;

                    if (item.logout) {
                        return (
                            <li key={item.label} className="shrink-0">
                                <Link
                                    href={logout()}
                                    as="button"
                                    className="text-foreground hover:bg-muted flex h-11 cursor-pointer items-center gap-2 rounded-full px-3 text-sm font-medium"
                                    data-test="account-logout-mobile"
                                >
                                    <Icon className="size-4 shrink-0" aria-hidden />
                                    {item.label}
                                </Link>
                            </li>
                        );
                    }

                    return (
                        <li key={item.label} className="shrink-0">
                            <Link
                                href={item.href!}
                                prefetch
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex h-11 items-center gap-2 rounded-full px-3 text-sm font-medium',
                                    active
                                        ? 'bg-muted text-foreground'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                )}
                            >
                                <Icon className="size-4 shrink-0" aria-hidden />
                                {item.label}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

export function AccountSidebar({ className }: AccountSidebarProps) {
    const { url } = usePage();

    return (
        <nav className={cn('flex flex-col gap-1', className)} aria-label="Account">
            {accountNavItems.map((item) => {
                const active = Boolean(item.match?.(url));
                const Icon = item.icon;

                if (item.logout) {
                    return (
                        <Link
                            key={item.label}
                            href={logout()}
                            as="button"
                            className="text-muted-foreground hover:bg-muted hover:text-foreground flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left text-[15px] font-medium transition-colors"
                            data-test="account-logout"
                        >
                            <Icon className="size-5 shrink-0" aria-hidden />
                            <span>{item.label}</span>
                        </Link>
                    );
                }

                return (
                    <Link
                        key={item.label}
                        href={item.href!}
                        prefetch
                        className={cn(
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-medium transition-colors',
                            active
                                ? 'bg-muted text-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )}
                        aria-current={active ? 'page' : undefined}
                    >
                        <Icon className="size-5 shrink-0" aria-hidden />
                        <span>{item.label}</span>
                    </Link>
                );
            })}
            <PwaInstallMenuItem className="text-muted-foreground hover:bg-muted hover:text-foreground flex items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-medium transition-colors [&_svg]:size-5" />
        </nav>
    );
}
