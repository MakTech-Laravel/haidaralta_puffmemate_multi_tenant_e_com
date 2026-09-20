import { Link, usePage } from '@inertiajs/react';
import { driverNavItems } from '@/components/driver/nav';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';

type DriverSidebarProps = {
    className?: string;
};

export function DriverSidebar({ className }: DriverSidebarProps) {
    const { url } = usePage();

    return (
        <nav
            className={cn(
                'border-border flex flex-row items-center justify-around gap-1 border-b pb-3 lg:flex-col lg:items-stretch lg:justify-start lg:gap-0 lg:border-b-0 lg:pb-0',
                className,
            )}
            aria-label="Driver"
        >
            {driverNavItems.map((item) => {
                const active = Boolean(item.match?.(url));
                const Icon = item.icon;

                if (item.logout) {
                    return (
                        <Link
                            key={item.label}
                            href={logout()}
                            as="button"
                            aria-label={item.label}
                            className={cn(
                                'text-foreground hover:text-primary flex cursor-pointer items-center justify-center gap-2 rounded-md p-3 transition-colors lg:justify-start lg:rounded-none lg:border-b lg:border-border lg:px-0 lg:py-3 lg:first:border-t',
                            )}
                            data-test="driver-logout"
                        >
                            <Icon className="size-5 shrink-0" aria-hidden />
                            <span className="hidden text-base font-medium lg:inline">
                                {item.label}
                            </span>
                        </Link>
                    );
                }

                return (
                    <Link
                        key={item.label}
                        href={item.href!}
                        prefetch
                        aria-label={item.label}
                        className={cn(
                            'flex items-center justify-center gap-2 rounded-md p-3 transition-colors lg:justify-start lg:rounded-none lg:border-b lg:border-border lg:px-0 lg:py-3 lg:first:border-t',
                            active
                                ? 'text-primary lg:border-primary'
                                : 'text-foreground hover:text-primary',
                        )}
                        aria-current={active ? 'page' : undefined}
                    >
                        <Icon className="size-5 shrink-0" aria-hidden />
                        <span className="hidden text-base font-medium lg:inline">
                            {item.label}
                        </span>
                    </Link>
                );
            })}
        </nav>
    );
}
