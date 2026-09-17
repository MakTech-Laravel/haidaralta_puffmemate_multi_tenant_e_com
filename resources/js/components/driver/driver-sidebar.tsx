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
        <nav className={cn('flex flex-col', className)} aria-label="Driver">
            {driverNavItems.map((item) => {
                const active = Boolean(item.match?.(url));

                if (item.logout) {
                    return (
                        <Link
                            key={item.label}
                            href={logout()}
                            as="button"
                            className="border-border text-foreground hover:text-primary cursor-pointer border-b py-3 text-left text-base font-medium transition-colors first:border-t"
                            data-test="driver-logout"
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
