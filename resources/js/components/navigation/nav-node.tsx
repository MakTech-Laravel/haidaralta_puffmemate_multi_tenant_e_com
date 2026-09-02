import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AnimatePresence, motion } from 'motion/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { cn, toUrl } from '@/lib/utils';
import { NavBadge } from './nav-badge';
import { NavFlyout } from './nav-flyout';
import { isNodeActive, isPathActive } from './nav-utils';
import type { NavNode, NavNodeClassNames } from './types';

interface NavNodeItemProps {
    node: NavNode;
    depth: number;
    currentPath: string;
    /** Default slot classes applied to every node (from `SidebarNav`). */
    defaults?: NavNodeClassNames;
}

/** Merge a slot's default + per-node override onto an optional base. */
function slot(
    key: keyof NavNodeClassNames,
    defaults: NavNodeClassNames | undefined,
    node: NavNode,
    base?: string,
): string | undefined {
    return cn(base, defaults?.[key], node.classNames?.[key]) || undefined;
}

/**
 * Recursive renderer for one navigation node.
 *
 *   - leaf            → link row (with tooltip when collapsed)
 *   - group, expanded → animated collapsible sub-tree
 *   - group, icon-collapsed & top-level → dropdown flyout
 */
export function NavNodeItem({
    node,
    depth,
    currentPath,
    defaults,
}: NavNodeItemProps) {
    const { state, isMobile } = useSidebar();
    const iconCollapsed = state === 'collapsed' && !isMobile;
    const isGroup = !!node.items?.length;

    if (isGroup) {
        if (iconCollapsed && depth === 0) {
            return (
                <NavFlyout
                    node={node}
                    currentPath={currentPath}
                    defaults={defaults}
                />
            );
        }

        return (
            <NavGroup
                node={node}
                depth={depth}
                currentPath={currentPath}
                defaults={defaults}
            />
        );
    }

    return (
        <NavLeaf
            node={node}
            depth={depth}
            currentPath={currentPath}
            defaults={defaults}
        />
    );
}

function NavRow({
    node,
    defaults,
}: {
    node: NavNode;
    defaults?: NavNodeClassNames;
}) {
    const Icon = node.icon;

    return (
        <>
            {Icon && <Icon className={slot('icon', defaults, node)} />}
            <span className={slot('title', defaults, node, 'flex-1 truncate')}>
                {node.title}
            </span>
            {node.badge != null && (
                <NavBadge className={slot('badge', defaults, node)}>
                    {node.badge}
                </NavBadge>
            )}
        </>
    );
}

function NavLeaf({ node, depth, currentPath, defaults }: NavNodeItemProps) {
    const { isMobile, setOpenMobile } = useSidebar();
    const active = isPathActive(node.href, currentPath, node.exact);

    const handleNavigate = () => {
        if (isMobile) {
            setOpenMobile(false);
        }
    };

    const child: ReactNode = node.disabled ? (
        <span className="cursor-not-allowed opacity-50">
            <NavRow node={node} defaults={defaults} />
        </span>
    ) : node.external ? (
        <a
            href={toUrl(node.href ?? '#')}
            target="_blank"
            rel="noopener noreferrer"
        >
            <NavRow node={node} defaults={defaults} />
        </a>
    ) : (
        <Link href={node.href ?? '#'} prefetch onClick={handleNavigate}>
            <NavRow node={node} defaults={defaults} />
        </Link>
    );

    if (depth === 0) {
        return (
            <SidebarMenuItem className={slot('item', defaults, node)}>
                <SidebarMenuButton
                    asChild
                    isActive={active}
                    tooltip={node.title}
                    aria-disabled={node.disabled}
                    className={slot('row', defaults, node)}
                >
                    {child}
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuSubItem className={slot('item', defaults, node)}>
            <SidebarMenuSubButton
                asChild
                isActive={active}
                aria-disabled={node.disabled}
                className={slot('row', defaults, node)}
            >
                {child}
            </SidebarMenuSubButton>
        </SidebarMenuSubItem>
    );
}

function NavGroup({ node, depth, currentPath, defaults }: NavNodeItemProps) {
    const active = isNodeActive(node, currentPath);
    const [open, setOpen] = useState(active);
    const [wasActive, setWasActive] = useState(active);

    // Auto-open the branch when it becomes the active one. Render-time sync is
    // React's recommended alternative to a setState-in-effect.
    if (active !== wasActive) {
        setWasActive(active);

        if (active) {
            setOpen(true);
        }
    }

    const trigger = (
        <>
            <NavRow node={node} defaults={defaults} />
            <motion.span
                animate={{ rotate: open ? 90 : 0 }}
                transition={{ duration: 0.2 }}
                className={slot('chevron', defaults, node, 'shrink-0')}
            >
                <ChevronRight className="h-4 w-4" />
            </motion.span>
        </>
    );

    const content = (
        <AnimatePresence initial={false}>
            {open && (
                <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.2, ease: 'easeInOut' }}
                    className="overflow-hidden"
                >
                    <SidebarMenuSub className={slot('sub', defaults, node)}>
                        {node.items?.map((child) => (
                            <NavNodeItem
                                key={child.title}
                                node={child}
                                depth={depth + 1}
                                currentPath={currentPath}
                                defaults={defaults}
                            />
                        ))}
                    </SidebarMenuSub>
                </motion.div>
            )}
        </AnimatePresence>
    );

    if (depth === 0) {
        return (
            <SidebarMenuItem className={slot('item', defaults, node)}>
                <SidebarMenuButton
                    isActive={active}
                    tooltip={node.title}
                    aria-expanded={open}
                    onClick={() => setOpen((value) => !value)}
                    className={slot('row', defaults, node)}
                >
                    {trigger}
                </SidebarMenuButton>
                {content}
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuSubItem className={slot('item', defaults, node)}>
            <SidebarMenuSubButton
                asChild
                isActive={active}
                className={slot('row', defaults, node)}
            >
                <button
                    type="button"
                    aria-expanded={open}
                    onClick={() => setOpen((value) => !value)}
                    className="w-full"
                >
                    {trigger}
                </button>
            </SidebarMenuSubButton>
            {content}
        </SidebarMenuSubItem>
    );
}
