import type { ReactNode } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function PortalSidebarHeader({
    accessory,
    accent = 'none',
    banner,
    breadcrumbs = [],
    density = 'default',
    sticky = false,
}: {
    accessory?: ReactNode;
    accent?: 'none' | 'primary-bar';
    banner?: ReactNode;
    breadcrumbs?: BreadcrumbItemType[];
    density?: 'compact' | 'default' | 'airy';
    sticky?: boolean;
}) {
    return (
        <header
            className={cn(
                'border-sidebar-border/50 flex shrink-0 flex-col border-b transition-[width,height] ease-linear',
                sticky &&
                    'bg-background/95 supports-backdrop-filter:bg-background/80 sticky top-0 z-20 backdrop-blur',
                density === 'compact' && 'min-h-12',
                density === 'default' &&
                    'min-h-16 group-has-data-[collapsible=icon]/sidebar-wrapper:min-h-12',
                density === 'airy' && 'min-h-20',
                accent === 'primary-bar' && 'border-t-primary border-t-4',
            )}
        >
            <div
                className={cn(
                    'flex items-center gap-3 px-6 md:px-4',
                    density === 'compact' && 'h-12',
                    density === 'default' && 'h-16',
                    density === 'airy' && 'h-20',
                )}
            >
                <div className="flex min-w-0 flex-1 items-center gap-2">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
                {accessory ? (
                    <div className="flex shrink-0 items-center gap-2">
                        {accessory}
                    </div>
                ) : null}
            </div>
            {banner ? (
                <div className="border-sidebar-border/50 border-t px-6 py-2 md:px-4">
                    {banner}
                </div>
            ) : null}
        </header>
    );
}
