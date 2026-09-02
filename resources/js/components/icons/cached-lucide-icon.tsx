import { Icon } from 'lucide-react';
import type { IconNode, LucideProps } from 'lucide-react';
import { useEffect, useState } from 'react';

import {
    getCachedLucideIconNode,
    preloadLucideIcon,
} from '@/lib/lucide-icon-cache';
import { cn } from '@/lib/utils';

export function CachedLucideIcon({
    name,
    className,
    fallbackClassName,
    ...props
}: {
    name: string;
    className?: string;
    fallbackClassName?: string;
} & Omit<LucideProps, 'ref'>) {
    const [iconName, setIconName] = useState(name);
    const [iconNode, setIconNode] = useState<IconNode | undefined>(() =>
        getCachedLucideIconNode(name),
    );

    if (name !== iconName) {
        setIconName(name);
        setIconNode(getCachedLucideIconNode(name));
    }

    const resolvedNode =
        name === iconName
            ? (getCachedLucideIconNode(name) ?? iconNode)
            : getCachedLucideIconNode(name);

    useEffect(() => {
        if (getCachedLucideIconNode(name)) {
            return;
        }

        let cancelled = false;

        void preloadLucideIcon(name).then((node) => {
            if (!cancelled && node) {
                setIconNode(node);
            }
        });

        return () => {
            cancelled = true;
        };
    }, [name]);

    if (!resolvedNode) {
        return (
            <span
                className={cn(
                    'inline-block animate-pulse rounded-sm bg-muted/50',
                    fallbackClassName ?? className,
                )}
                aria-hidden
            />
        );
    }

    return (
        <Icon
            iconNode={resolvedNode}
            className={className}
            aria-hidden={props['aria-hidden'] ?? true}
            {...props}
        />
    );
}
