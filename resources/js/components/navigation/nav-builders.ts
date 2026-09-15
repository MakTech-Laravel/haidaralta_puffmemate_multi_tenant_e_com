import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { PermissionKey } from '@/types/permissions';
import type { NavNode, NavNodeClassNames, NavSection } from './types';

type Href = NonNullable<InertiaLinkProps['href']>;

type NavLinkOptions = {
    title: string;
    href?: Href;
    icon?: LucideIcon;
    exact?: boolean;
    badge?: string | number;
    disabled?: boolean;
    external?: boolean;
    permissions?: PermissionKey[];
    classNames?: NavNodeClassNames;
};

type NavSoonOptions = {
    title: string;
    icon?: LucideIcon;
    permissions?: PermissionKey[];
    classNames?: NavNodeClassNames;
};

type NavGroupOptions = {
    title: string;
    href?: Href;
    icon?: LucideIcon;
    exact?: boolean;
    badge?: string | number;
    disabled?: boolean;
    permissions?: PermissionKey[];
    items: NavNode[];
    classNames?: NavNodeClassNames;
};

/**
 * Build a leaf sidebar link. Prefer this over raw `NavNode` objects in portal
 * `nav.ts` files so title/href/permissions stay consistent.
 */
export function navLink(options: NavLinkOptions): NavNode {
    return {
        title: options.title,
        href: options.href,
        icon: options.icon,
        exact: options.exact,
        badge: options.badge,
        disabled: options.disabled,
        external: options.external,
        permissions: options.permissions,
        classNames: options.classNames,
    };
}

/**
 * Build a collapsible sidebar group (optional landing `href` on the parent row).
 * Children should themselves be created with `navLink` / `navGroup`.
 */
export function navGroup(options: NavGroupOptions): NavNode {
    return {
        title: options.title,
        href: options.href,
        icon: options.icon,
        exact: options.exact,
        badge: options.badge,
        disabled: options.disabled,
        permissions: options.permissions,
        items: options.items,
        classNames: options.classNames,
    };
}

/**
 * Disabled roadmap row. No href — must not visit a route. Badge defaults to Soon.
 */
export function navSoon(options: NavSoonOptions): NavNode {
    return {
        title: options.title,
        icon: options.icon,
        disabled: true,
        badge: 'Soon',
        permissions: options.permissions,
        classNames: options.classNames,
    };
}

/** Labeled (or unlabeled) sidebar section wrapping `navLink` / `navGroup` items. */
export function navSection(
    label: string | undefined,
    items: NavNode[],
): NavSection {
    return { label, items };
}
