import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { PermissionKey } from '@/types/permissions';

/**
 * Per-slot class overrides for a nav row. Every slot is optional; provided
 * classes are merged onto (and can override, via tailwind-merge) the defaults.
 *
 * Set defaults for every item through `SidebarNav`'s `classNames` prop, then
 * override individual items with their own `classNames`.
 */
export interface NavNodeClassNames {
    /** The `<li>` wrapper (`SidebarMenuItem` / `SidebarMenuSubItem`). */
    item?: string;
    /**
     * The row button/link — this IS the flex container. Put alignment here:
     * `justify-start` (default), `justify-between`, `justify-end`, `gap-*`,
     * padding, height, etc.
     */
    row?: string;
    /** Leading icon. */
    icon?: string;
    /**
     * Title text. Defaults to `flex-1 truncate`; override (e.g. `flex-none`)
     * to change how it shares space with the trailing badge/chevron.
     */
    title?: string;
    /** Trailing badge pill. */
    badge?: string;
    /** Group expand/collapse chevron. */
    chevron?: string;
    /** The nested children container (`SidebarMenuSub`). */
    sub?: string;
}

/**
 * A single sidebar navigation entry (complete portal nav contract).
 *
 * Prefer {@link navLink} / {@link navGroup} from `nav-builders` when defining
 * portal trees (`platform/nav.ts`, `branch/nav.ts`, etc.). Do not fork portal
 * sidebars via legacy `AppSidebar` / `NavMain`.
 *
 * Supported options:
 * - **Leaf link** — `title` + `href` (+ optional icon, badge, exact, external, disabled)
 * - **Group / dropdown** — `items` (collapsible; icon-rail flyout when collapsed)
 * - **Group + landing href** — parent may also have `href`
 * - **Nested groups** — `items` recurse to any depth
 * - **Permissions** — `permissions` show when user has ANY; omit = always visible
 * - **classNames** — per-row slot overrides (defaults via `SidebarNav`)
 *
 * Section labels live on the layout (`groupLabel` / `PortalShellLayout`), not
 * on individual nodes.
 */
export interface NavNode {
    /** Visible label. */
    title: string;
    /** Destination. Omit for pure grouping rows. Accepts Wayfinder route objects. */
    href?: NonNullable<InertiaLinkProps['href']>;
    /**
     * When true, only the exact path is active (no child-path matching).
     * Use for section roots like `/platform` that would otherwise match every
     * nested page.
     */
    exact?: boolean;
    /** Leading icon (Lucide component). */
    icon?: LucideIcon;
    /** Trailing badge (count or short text). */
    badge?: string | number;
    /** Render but disable interaction. */
    disabled?: boolean;
    /** Open in a new tab via a plain anchor instead of an Inertia visit. */
    external?: boolean;
    /**
     * Permission gate — the node is shown when the user has ANY of these.
     * Omit to always show (subject to children visibility for groups).
     * `filterNavNodes` also drops empty groups that have no own `href`.
     */
    permissions?: PermissionKey[];
    /** Nested children. Presence of items makes this a group. */
    items?: NavNode[];
    /** Per-slot class overrides for this row. */
    classNames?: NavNodeClassNames;
}
