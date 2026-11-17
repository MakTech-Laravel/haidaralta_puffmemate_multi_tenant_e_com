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
                'border-border bg-background/85 supports-[backdrop-filter]:bg-background/75 fixed inset-x-0 bottom-0 z-40 border-t pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden',
                className,
            )}
            aria-label="Account"
        >
            <ul className="mx-auto flex max-w-lg items-stretch justify-around px-1 pt-1.5 pb-1">
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
                                    className="text-foreground hover:text-primary flex w-full cursor-pointer flex-col items-center gap-1 px-0.5 py-1 transition-colors"
                                    data-test="account-mobile-logout"
                                >
                                    <Icon className="size-5" aria-hidden />
                                    <span className="max-w-full truncate text-[10px] leading-none font-medium">
                                        {item.shortLabel ?? item.label}
                                    </span>
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
                                    'flex w-full flex-col items-center gap-1 px-0.5 py-1 transition-colors',
                                    active
                                        ? 'text-primary'
                                        : 'text-foreground hover:text-primary',
                                )}
                                aria-current={active ? 'page' : undefined}
                            >
                                <Icon className="size-5" aria-hidden />
                                <span className="max-w-full truncate text-[10px] leading-none font-medium">
                                    {item.shortLabel ?? item.label}
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
