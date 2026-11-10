import { Link, usePage } from '@inertiajs/react';
import { driverTabItems } from '@/components/driver/nav';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
            <ul
                className="grid h-16"
                style={{
                    gridTemplateColumns: `repeat(${driverTabItems.length}, minmax(0, 1fr))`,
                }}
            >
                {driverTabItems.map((item) => {
                    const active = item.match(url);
                    const Icon = item.icon;
                    const tip = item.tabLabel ?? item.label;

                    return (
                        <li key={item.id} className="min-w-0">
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Link
                                        href={item.href!}
                                        prefetch
                                        aria-label={item.label}
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        data-test={`driver-tab-${item.id}`}
                                        className={cn(
                                            'flex h-full min-h-14 flex-col items-center justify-center px-1 transition-colors',
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
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent side="top">{tip}</TooltipContent>
                            </Tooltip>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
