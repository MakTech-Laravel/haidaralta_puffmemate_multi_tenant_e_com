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
                'inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-md bg-primary/10 px-1.5 text-xs font-medium text-primary tabular-nums',
                'group-data-[collapsible=icon]:hidden',
                className,
            )}
        >
            {children}
        </span>
    );
}
