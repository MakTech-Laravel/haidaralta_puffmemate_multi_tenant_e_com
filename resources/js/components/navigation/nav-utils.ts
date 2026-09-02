import type { PermissionKey } from '@/types/permissions';
import type { NavNode } from '@/components/navigation/types';

export function filterNavNodes(
    nodes: NavNode[],
    canAny: (permissions: PermissionKey[]) => boolean,
): NavNode[] {
    return nodes
        .map((node) => {
            const items = node.items
                ? filterNavNodes(node.items, canAny)
                : undefined;
            const visible =
                !node.permissions || canAny(node.permissions);

            if (!visible && (!items || items.length === 0)) {
                return null;
            }

            return { ...node, items };
        })
        .filter((node): node is NavNode => node !== null);
}
