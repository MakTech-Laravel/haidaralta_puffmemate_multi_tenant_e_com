import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Trailing pill badge for a nav row. Hidden automatically when the sidebar
 * collapses to icon mode.
 */
export function NavBadge({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'bg-primary/10 text-primary inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-md px-1.5 text-xs font-medium tabular-nums',
                'group-data-[collapsible=icon]:hidden',
                className,
            )}
        >
            {children}
        </span>
    );
}
