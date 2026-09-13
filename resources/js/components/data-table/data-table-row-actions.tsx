import { Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
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
    href?: string;
    onSelect?: () => void;
    destructive?: boolean;
    separatorBefore?: boolean;
    disabled?: boolean;
};

function ActionItem({ item }: { item: DataTableActionItem }) {
    const content: ReactNode = item.label;

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
}: {
    items: DataTableActionItem[];
    label?: string;
}) {
    return (
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
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuLabel>{label}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {items.map((item) => (
                    <Fragment key={item.label}>
                        {item.separatorBefore ? <DropdownMenuSeparator /> : null}
                        <ActionItem item={item} />
                    </Fragment>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
