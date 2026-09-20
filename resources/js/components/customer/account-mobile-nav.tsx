import { Link, usePage } from '@inertiajs/react';
import { accountNavItems } from '@/components/customer/account-sidebar';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';

type AccountMobileNavProps = {
    className?: string;
};

export function AccountMobileNav({ className }: AccountMobileNavProps) {
    const { url } = usePage();
    const items = accountNavItems.filter((item) => item.mobile !== false);

    return (
        <nav
            className={cn(
                'border-border bg-background fixed inset-x-0 bottom-0 z-40 border-t lg:hidden',
                className,
            )}
            aria-label="Account"
        >
            <ul className="mx-auto flex max-w-lg items-stretch justify-around px-1 py-2">
                {items.map((item) => {
                    const active = Boolean(item.match?.(url));
                    const Icon = item.icon;

                    if (item.logout) {
                        return (
                            <li key={item.label} className="flex-1">
                                <Link
                                    href={logout()}
                                    as="button"
                                    aria-label={item.label}
                                    className="text-foreground hover:text-primary flex w-full cursor-pointer flex-col items-center gap-1.5 py-1 transition-colors"
                                    data-test="account-mobile-logout"
                                >
                                    <Icon className="size-5" aria-hidden />
                                    <span
                                        className="bg-foreground h-0.5 w-5 rounded-full"
                                        aria-hidden
                                    />
                                </Link>
                            </li>
                        );
                    }

                    return (
                        <li key={item.label} className="flex-1">
                            <Link
                                href={item.href!}
                                prefetch
                                aria-label={item.label}
                                className={cn(
                                    'flex w-full flex-col items-center gap-1.5 py-1 transition-colors',
                                    active
                                        ? 'text-primary'
                                        : 'text-foreground hover:text-primary',
                                )}
                                aria-current={active ? 'page' : undefined}
                            >
                                <Icon className="size-5" aria-hidden />
                                <span
                                    className={cn(
                                        'h-0.5 w-5 rounded-full',
                                        active ? 'bg-primary' : 'bg-foreground',
                                    )}
                                    aria-hidden
                                />
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
