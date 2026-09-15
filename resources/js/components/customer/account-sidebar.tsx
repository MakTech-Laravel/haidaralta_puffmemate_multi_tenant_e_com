import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

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
        href: '/activity',
        // Orders shares /activity until a dedicated orders route exists;
        // My activity owns the active state on that path.
        match: () => false,
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
        href: '/dashboard',
        match: () => false,
    },
    {
        label: 'Account',
        href: editProfile.url(),
        match: (url) => url.startsWith('/settings'),
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
