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
            className={cn('hidden flex-col gap-1 xl:flex', className)}
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
                            className="text-muted-foreground hover:bg-muted hover:text-foreground flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left text-[15px] font-medium transition-colors"
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
        </nav>
    );
}
