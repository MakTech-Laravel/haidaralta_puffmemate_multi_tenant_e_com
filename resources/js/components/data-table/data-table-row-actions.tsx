import { Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import type { ReactNode } from 'react';
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
                {items.map((item) => {
                    const content: ReactNode = item.label;

                    if (item.separatorBefore) {
                        return (
                            <div key={`sep-${item.label}`}>
                                <DropdownMenuSeparator />
                                {item.href ? (
                                    <DropdownMenuItem
                                        variant={
                                            item.destructive
                                                ? 'destructive'
                                                : 'default'
                                        }
                                        disabled={item.disabled}
                                        asChild
                                    >
                                        <Link href={item.href}>{content}</Link>
                                    </DropdownMenuItem>
                                ) : (
                                    <DropdownMenuItem
                                        variant={
                                            item.destructive
                                                ? 'destructive'
                                                : 'default'
                                        }
                                        disabled={item.disabled}
                                        onSelect={item.onSelect}
                                    >
                                        {content}
                                    </DropdownMenuItem>
                                )}
                            </div>
                        );
                    }

                    if (item.href) {
                        return (
                            <DropdownMenuItem
                                key={item.label}
                                variant={
                                    item.destructive ? 'destructive' : 'default'
                                }
                                disabled={item.disabled}
                                asChild
                            >
                                <Link href={item.href}>{content}</Link>
                            </DropdownMenuItem>
                        );
                    }

                    return (
                        <DropdownMenuItem
                            key={item.label}
                            variant={
                                item.destructive ? 'destructive' : 'default'
                            }
                            disabled={item.disabled}
                            onSelect={item.onSelect}
                        >
                            {content}
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
