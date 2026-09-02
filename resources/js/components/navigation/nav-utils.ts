import type { InertiaLinkProps } from '@inertiajs/react';
import { toUrl } from '@/lib/utils';
import type { PermissionKey } from '@/types/permissions';
import type { NavNode } from './types';

type Href = NonNullable<InertiaLinkProps['href']>;

/** Normalise any href (string or Wayfinder object, relative or absolute) to a pathname. */
export function toPath(href?: Href): string | null {
    if (!href) {
        return null;
    }

    const raw = toUrl(href);

    if (!raw || raw === '#') {
        return null;
    }

    let path = raw;

    if (raw.startsWith('http')) {
        try {
            path = new URL(raw).pathname;
        } catch {
            return null;
        }
    }

    path = path.split('?')[0].split('#')[0];

    // Drop a trailing slash, except for the root "/".
    return path.length > 1 ? path.replace(/\/+$/, '') : path;
}

/**
 * Whether `href` matches the current path. Matches the exact path or a
 * sub-path (`/admin/users` is active on `/admin/users/5`), but never a sibling
 * that merely shares a prefix (`/admin/users` is NOT active on `/admin/users-archive`).
 * Pass `exact` for section roots (e.g. `/platform`) that must not match children.
 */
export function isPathActive(
    href: Href | undefined,
    currentPath: string,
    exact = false,
): boolean {
    const target = toPath(href);

    if (!target) {
        return false;
    }

    const current =
        currentPath.length > 1 ? currentPath.replace(/\/+$/, '') : currentPath;

    if (exact) {
        return current === target;
    }

    return current === target || current.startsWith(`${target}/`);
}

/** A node is active when it (a leaf) matches, or any descendant leaf matches. */
export function isNodeActive(node: NavNode, currentPath: string): boolean {
    if (node.items?.length) {
        return node.items.some((child) => isNodeActive(child, currentPath));
    }

    return isPathActive(node.href, currentPath, node.exact);
}

/**
 * Filter a tree by permissions:
 *   - drop any node whose explicit `permissions` the user lacks;
 *   - recurse into groups and drop empty groups that have no own link.
 */
export function filterNavNodes(
    nodes: NavNode[],
    canAny: (permissions: PermissionKey[]) => boolean,
): NavNode[] {
    const result: NavNode[] = [];

    for (const node of nodes) {
        if (node.permissions?.length && !canAny(node.permissions)) {
            continue;
        }

        if (node.items?.length) {
            const items = filterNavNodes(node.items, canAny);

            if (items.length === 0 && !node.href) {
                continue;
            }

            result.push({ ...node, items });
        } else {
            result.push(node);
        }
    }

    return result;
}
