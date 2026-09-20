import { Link, usePage } from '@inertiajs/react';
import { Bell, UserRound } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { driverNavItems } from '@/components/driver/nav';
import { useInitials } from '@/hooks/use-initials';
import { storefrontLogoSrc } from '@/lib/storefront-chrome';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/driver';
import { index as accountIndex } from '@/routes/driver/account';
import { index as notificationsIndex } from '@/routes/driver/notifications';
import type { Auth } from '@/types';

/**
 * Field header: brand on the left, alerts + account on the right.
 * Matches the storefront icon row so thumbs hit the same targets.
 */
export function DriverHeader() {
    const { url, props } = usePage<{ auth: Auth; name: string }>();
    const user = props.auth.user;
    const getInitials = useInitials();
    const notificationsHref = notificationsIndex.url();
    const accountHref = accountIndex.url();
    const notificationsActive =
        url === notificationsHref ||
        url.startsWith(`${notificationsHref}?`) ||
        url.startsWith(`${notificationsHref}/`);
    const accountActive = Boolean(
        driverNavItems.find((item) => item.id === 'account')?.match(url),
    );

    return (
        <header className="border-border bg-background/95 sticky top-0 z-40 border-b pt-[env(safe-area-inset-top)] backdrop-blur-sm">
            <div className="flex h-14 items-center gap-3 px-4 sm:h-16 sm:px-6">
                <Link
                    href={dashboard.url()}
                    prefetch
                    className="flex min-w-0 items-center gap-2"
                >
                    <img
                        src={storefrontLogoSrc}
                        alt={props.name}
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
                    </Link>

                    <Link
                        href={accountHref}
                        prefetch
                        aria-label="Account"
                        aria-current={accountActive ? 'page' : undefined}
                        data-test="driver-header-account"
                        className={cn(
                            'relative inline-flex size-11 items-center justify-center rounded-md transition-colors',
                            accountActive ? 'text-primary' : 'text-foreground',
                        )}
                    >
                        {user?.avatar ? (
                            <Avatar className="size-8">
                                <AvatarImage
                                    src={user.avatar}
                                    alt={user.name}
                                />
                                <AvatarFallback className="text-xs">
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                        ) : (
                            <UserRound className="size-6" strokeWidth={1.75} />
                        )}
                    </Link>
                </div>
            </div>
        </header>
    );
}
