import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { index as accountIndex } from '@/routes/customer/account';
import { index as productToReview } from '@/routes/customer/product-to-review';

export type AccountNavItem = {
    label: string;
    href?: string;
    match?: (url: string) => boolean;
    logout?: boolean;
};

export const accountNavItems: AccountNavItem[] = [
    {
        label: 'Dashboard',
        href: '/dashboard',
        match: (url) => url === '/dashboard' || url.startsWith('/dashboard?'),
    },
    {
        label: 'Orders',
        href: '/orders',
        match: (url) =>
            url === '/orders' || url.startsWith('/orders?') || url.startsWith('/orders/'),
    },
    {
        label: 'My activity',
        href: '/activity',
        match: (url) =>
            url === '/activity' ||
            url.startsWith('/activity?') ||
            url.startsWith('/activity/'),
    },
    {
        label: 'Product to review',
        href: productToReview.url(),
        match: (url) =>
            url === '/product-to-review' ||
            url.startsWith('/product-to-review?') ||
            url.startsWith('/product-to-review/'),
    },
    {
        label: 'Account',
        href: accountIndex.url(),
        match: (url) =>
            url === '/account' || url.startsWith('/account?'),
    },
    {
        label: 'Log Out',
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

                if (item.logout) {
                    return (
                        <Link
                            key={item.label}
                            href={logout()}
                            as="button"
                            className="border-border text-foreground hover:text-primary border-b py-3 text-left text-base font-medium transition-colors first:border-t"
                            data-test="account-logout"
                        >
                            {item.label}
                        </Link>
                    );
                }

                return (
                    <Link
                        key={item.label}
                        href={item.href!}
                        prefetch
                        className={cn(
                            'border-border border-b py-3 text-base font-medium transition-colors first:border-t',
                            active
                                ? 'text-primary border-primary'
                                : 'text-foreground hover:text-primary',
                        )}
                        aria-current={active ? 'page' : undefined}
                    >
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}
