import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { DriverAccountSheet } from '@/components/driver/driver-account-sheet';
import { useAppBrand } from '@/hooks/use-app-brand';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/driver';
import { index as notificationsIndex } from '@/routes/driver/notifications';
import type { Auth } from '@/types';

/**
 * Field header: brand on the left, alerts + account sheet on the right.
 */
export function DriverHeader() {
    const { url, props } = usePage<{
        auth: Auth;
        name: string;
        notifications?: { unreadCount: number };
    }>();
    const brand = useAppBrand();
    const user = props.auth.user;
    const notificationsHref = notificationsIndex.url();
    const notificationsActive =
        url === notificationsHref ||
        url.startsWith(`${notificationsHref}?`) ||
        url.startsWith(`${notificationsHref}/`);

    return (
        <header className="border-border bg-background/95 sticky top-0 z-40 border-b pt-[env(safe-area-inset-top)] backdrop-blur-sm">
            <div className="flex h-14 items-center gap-3 px-4 sm:h-16 sm:px-6">
                <Link
                    href={dashboard.url()}
                    prefetch
                    className="flex min-w-0 items-center gap-2"
                >
                    <img
                        src={brand.logoUrl}
                        alt={brand.name}
                        className="h-8 w-auto object-contain sm:h-9"
                    />
                    <span className="text-muted-foreground hidden text-xs font-semibold tracking-[0.12em] uppercase sm:inline">
                        Driver
                    </span>
                </Link>

                <div className="ml-auto flex items-center gap-0.5 sm:gap-1">
                    <Link
                        href={notificationsHref}
                        prefetch
                        aria-label="Notifications"
                        aria-current={notificationsActive ? 'page' : undefined}
                        data-test="driver-header-notifications"
                        className={cn(
                            'relative inline-flex size-11 items-center justify-center rounded-md transition-colors',
                            notificationsActive
                                ? 'text-primary'
                                : 'text-foreground',
                        )}
                    >
                        <Bell className="size-6" strokeWidth={1.75} />
                        {(props.notifications?.unreadCount ?? 0) > 0 ? (
                            <span className="bg-primary absolute top-2 right-2 size-2 rounded-full" />
                        ) : null}
                    </Link>

                    <DriverAccountSheet user={user} />
                </div>
            </div>
        </header>
    );
}
