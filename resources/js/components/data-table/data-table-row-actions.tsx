import { Link } from '@inertiajs/react';
import {
    Download,
    Eye,
    MoreHorizontal,
    Pencil,
    RotateCcw,
    Settings2,
    Trash2,
    type LucideIcon,
} from 'lucide-react';
import { Fragment, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
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
    separatorBefore?: boolean;
    disabled?: boolean;
};

/** Default icons keyed by action label; used when `icon` is not passed. */
export const defaultDataTableActionIcons: Record<string, LucideIcon> = {
    View: Eye,
    Edit: Pencil,
    Delete: Trash2,
    Restore: RotateCcw,
    Download: Download,
    Manage: Settings2,
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

export function DataTableRowActions({
    items,
    label = 'Actions',
    contentClassName = 'w-44',
}: {
    items: DataTableActionItem[];
    label?: string;
    contentClassName?: string;
}) {
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
                <DropdownMenuContent
                    align="end"
                    className={contentClassName}
                >
                    <DropdownMenuLabel>{label}</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {items.map((item) => (
                        <Fragment key={item.label}>
                            {item.separatorBefore ? (
                                <DropdownMenuSeparator />
                            ) : null}
                            <ActionItem item={item} />
                        </Fragment>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
