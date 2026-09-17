import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Bell,
    LayoutGrid,
    LogOut,
    ShoppingBag,
    Star,
    User,
    type LucideIcon,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { index as accountIndex } from '@/routes/customer/account';
import { index as notificationsIndex } from '@/routes/customer/notifications';
import { index as productToReview } from '@/routes/customer/product-to-review';

export type AccountNavItem = {
    label: string;
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
        label: 'Product to review',
        icon: Star,
        href: productToReview.url(),
        match: (url) =>
            url === '/product-to-review' ||
            url.startsWith('/product-to-review?') ||
            url.startsWith('/product-to-review/'),
    },
    {
        label: 'Notification',
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
        icon: LogOut,
        logout: true,
    },
];

type AccountSidebarProps = {
    className?: string;
};

export function AccountSidebar({ className }: AccountSidebarProps) {
    const { url } = usePage();

    return (
        <nav className={cn('flex flex-col', className)} aria-label="Account">
            {accountNavItems.map((item) => {
                const active = Boolean(item.match?.(url));
                const Icon = item.icon;

                if (item.logout) {
                    return (
                        <Link
                            key={item.label}
                            href={logout()}
                            as="button"
                            className="border-border text-foreground hover:text-primary flex cursor-pointer items-center gap-3 border-b py-3 text-left text-base font-medium transition-colors first:border-t"
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
                            'flex items-center gap-3 border-b py-3 text-base font-medium transition-colors first:border-t',
                            active
                                ? 'text-primary border-primary'
                                : 'border-border text-foreground hover:text-primary',
                        )}
                        aria-current={active ? 'page' : undefined}
                    >
                        <Icon className="size-5 shrink-0" aria-hidden />
                        <span>{item.label}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
