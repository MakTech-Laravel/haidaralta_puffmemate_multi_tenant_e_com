import { Link, usePage } from '@inertiajs/react';
import { driverTabItems } from '@/components/driver/nav';
import { cn } from '@/lib/utils';

/**
 * Social-style tab bar for phones and tablets. Hidden from xl up — desktop
 * uses the sidebar instead, and must not grow a second footer.
 */
export function DriverMobileNav() {
    const { url } = usePage();

    return (
        <nav
            className="border-border bg-background/95 fixed inset-x-0 bottom-0 z-40 border-t pb-[env(safe-area-inset-bottom)] backdrop-blur-sm xl:hidden"
            aria-label="Driver"
        >
            <ul className="grid h-16 grid-cols-5">
                {driverTabItems.map((item) => {
                    const active = item.match(url);
                    const Icon = item.icon;

                    return (
                        <li key={item.id} className="min-w-0">
                            <Link
                                href={item.href!}
                                prefetch
                                aria-label={item.label}
                                aria-current={active ? 'page' : undefined}
                                data-test={`driver-tab-${item.id}`}
                                className={cn(
                                    'flex h-full min-h-14 flex-col items-center justify-center gap-0.5 px-1 transition-colors',
                                    active
                                        ? 'text-primary'
                                        : 'text-muted-foreground',
                                )}
                            >
                                <Icon
                                    className="size-6 shrink-0"
                                    strokeWidth={active ? 2.25 : 1.75}
                                    aria-hidden
                                />
                                <span className="max-w-full truncate text-[11px] leading-none font-medium">
                                    {item.tabLabel ?? item.label}
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
