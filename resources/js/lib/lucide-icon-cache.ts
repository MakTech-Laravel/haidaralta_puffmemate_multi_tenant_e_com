import type { IconNode } from 'lucide-react';
import dynamicIconImports from 'lucide-react/dynamicIconImports';

const pendingIcons = new Map<string, Promise<IconNode | null>>();
const resolvedIcons = new Map<string, IconNode>();

export function getCachedLucideIconNode(name: string): IconNode | undefined {
    return resolvedIcons.get(name);
}

export function preloadLucideIcon(name: string): Promise<IconNode | null> {
    const cached = resolvedIcons.get(name);

    if (cached) {
        return Promise.resolve(cached);
    }

    const pending = pendingIcons.get(name);

    if (pending) {
        return pending;
    }

    const loader = dynamicIconImports[name as keyof typeof dynamicIconImports];

    if (!loader) {
        return Promise.resolve(null);
    }

    const promise = loader()
        .then((module) => {
            const iconNode = module.__iconNode as IconNode;
            resolvedIcons.set(name, iconNode);

            return iconNode;
        })
        .catch(() => null)
        .finally(() => {
            pendingIcons.delete(name);
        });

    pendingIcons.set(name, promise);

    return promise;
}

export function prefetchLucideIcons(names: string[]): void {
    for (const name of names) {
        void preloadLucideIcon(name);
    }
}

export function clearLucideIconCache(): void {
    pendingIcons.clear();
    resolvedIcons.clear();
}
