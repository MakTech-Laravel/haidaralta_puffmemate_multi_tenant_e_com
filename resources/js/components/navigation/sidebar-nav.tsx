import { useMemo } from 'react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePermission } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { NavNodeItem } from './nav-node';
import { filterNavNodes } from './nav-utils';
import type { NavNode, NavNodeClassNames } from './types';

interface SidebarNavProps {
    items: NavNode[];
    /** Optional group heading (hidden automatically in icon mode). */
    label?: string;
    /** Class for the wrapping `SidebarGroup`. */
    className?: string;
    /**
     * Default per-slot classes applied to every item. Individual items can
     * still override these via their own `classNames`.
     */
    classNames?: NavNodeClassNames;
}

/**
 * Permission-aware, infinitely-nestable navigation for the shadcn Sidebar.
 * Renders nothing if the user can see none of the supplied items.
 */
export function SidebarNav({
    items,
    label,
    className,
    classNames,
}: SidebarNavProps) {
    const { canAny } = usePermission();
    const { currentUrl } = useCurrentUrl();

    const visible = useMemo(
        () => filterNavNodes(items, canAny),
        [items, canAny],
    );

    if (visible.length === 0) {
        return null;
    }

    return (
        <SidebarGroup className={cn('px-2 py-0', className)}>
            {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
            <SidebarMenu>
                {visible.map((node) => (
                    <NavNodeItem
                        key={node.title}
                        node={node}
                        depth={0}
                        currentPath={currentUrl}
                        defaults={classNames}
                    />
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
