import { Link } from '@inertiajs/react';
import {
    Download,
    Eye,
    History,
    MoreHorizontal,
    Network,
    Package,
    Pencil,
    RotateCcw,
    ScrollText,
    Settings2,
    Trash2,
    Truck,
    Users,
    type LucideIcon,
} from 'lucide-react';
import { Fragment, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export type DataTableActionItem = {
    label: string;
    /** Lucide icon component. When omitted, falls back by `label`. */
    icon?: LucideIcon;
    href?: string;
    onSelect?: () => void;
    destructive?: boolean;
    /** @deprecated Prefer `groups` on DataTableRowActions for labeled sections. */
    separatorBefore?: boolean;
    disabled?: boolean;
};

export type DataTableActionGroup = {
    /** Section heading shown above the items. Omit for an unlabeled block. */
    label?: string;
    items: DataTableActionItem[];
};

/** Default icons keyed by action label; used when `icon` is not passed. */
export const defaultDataTableActionIcons: Record<string, LucideIcon> = {
    View: Eye,
    Edit: Pencil,
    Delete: Trash2,
    Restore: RotateCcw,
    Download: Download,
    Manage: Settings2,
    Staff: Users,
    Products: Package,
    Drivers: Truck,
    'Manage stock': Settings2,
    'Manage branches': Network,
    'Transfer branch': Network,
    'Stock transactions': ScrollText,
    'Price history': History,
    'Restore DB': RotateCcw,
    'Delete zip': Trash2,
};

function resolveIcon(item: DataTableActionItem): LucideIcon | null {
    return item.icon ?? defaultDataTableActionIcons[item.label] ?? null;
}

function ActionItem({ item }: { item: DataTableActionItem }) {
    const Icon = resolveIcon(item);
    const content: ReactNode = (
        <>
            {Icon ? <Icon className="size-4" /> : null}
            <span>{item.label}</span>
        </>
    );

    if (item.href) {
        return (
            <DropdownMenuItem
                variant={item.destructive ? 'destructive' : 'default'}
                disabled={item.disabled}
                asChild
            >
                <Link href={item.href}>{content}</Link>
            </DropdownMenuItem>
        );
    }

    return (
        <DropdownMenuItem
            variant={item.destructive ? 'destructive' : 'default'}
            disabled={item.disabled}
            onSelect={(event) => {
                // Keep the menu from swallowing the click before the confirm dialog opens.
                event.preventDefault();
                item.onSelect?.();
            }}
        >
            {content}
        </DropdownMenuItem>
    );
}

function resolveGroups(
    items: DataTableActionItem[] | undefined,
    groups: DataTableActionGroup[] | undefined,
): DataTableActionGroup[] {
    if (groups && groups.length > 0) {
        return groups.filter((group) => group.items.length > 0);
    }

    if (!items || items.length === 0) {
        return [];
    }

    // Preserve legacy separatorBefore by splitting into unlabeled groups.
    const legacy: DataTableActionGroup[] = [];
    let current: DataTableActionItem[] = [];

    for (const item of items) {
        if (item.separatorBefore && current.length > 0) {
            legacy.push({ items: current });
            current = [];
        }

        current.push(item);
    }

    if (current.length > 0) {
        legacy.push({ items: current });
    }

    return legacy;
}

export function DataTableRowActions({
    items,
    groups,
    label = 'Actions',
    contentClassName = 'w-44',
}: {
    items?: DataTableActionItem[];
    groups?: DataTableActionGroup[];
    label?: string;
    contentClassName?: string;
}) {
    const resolvedGroups = resolveGroups(items, groups);

    return (
        <div className="flex justify-end">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        aria-label={label}
                    >
                        <MoreHorizontal className="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className={contentClassName}>
                    <DropdownMenuLabel>{label}</DropdownMenuLabel>
                    {resolvedGroups.map((group, groupIndex) => (
                        <Fragment
                            key={`${group.label ?? 'group'}-${groupIndex}`}
                        >
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                {group.label ? (
                                    <DropdownMenuLabel className="text-muted-foreground px-2 py-1.5 text-xs font-medium">
                                        {group.label}
                                    </DropdownMenuLabel>
                                ) : null}
                                {group.items.map((item) => (
                                    <ActionItem key={item.label} item={item} />
                                ))}
                            </DropdownMenuGroup>
                        </Fragment>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
