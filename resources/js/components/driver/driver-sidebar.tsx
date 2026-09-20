import { Link, usePage } from '@inertiajs/react';
import { driverNavItems } from '@/components/driver/nav';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';

type DriverSidebarProps = {
    className?: string;
};

/**
 * Desktop-only list. Phones and tablets use DriverMobileNav instead.
 */
export function DriverSidebar({ className }: DriverSidebarProps) {
    const { url } = usePage();

    return (
        <nav
            className={cn('hidden flex-col xl:flex', className)}
            aria-label="Driver"
        >
            {driverNavItems.map((item) => {
                const active = item.match(url);
                const Icon = item.icon;

                if (item.logout) {
                    return (
                        <Link
                            key={item.id}
                            href={logout()}
                            as="button"
                            className="border-border text-foreground hover:text-primary flex cursor-pointer items-center gap-3 border-b px-0 py-3 text-left text-base font-medium transition-colors first:border-t"
                            data-test="driver-logout"
                        >
                            <Icon className="size-5 shrink-0" aria-hidden />
                            <span>{item.label}</span>
                        </Link>
                    );
                }

                return (
                    <Link
                        key={item.id}
                        href={item.href!}
                        prefetch
                        className={cn(
                            'flex items-center gap-3 border-b px-0 py-3 text-base font-medium transition-colors first:border-t',
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
