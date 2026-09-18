import { Link } from '@inertiajs/react';
import type { DataTableLink, DataTableMeta } from '@/components/data-table/types';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const PER_PAGE_OPTIONS = [15, 25, 50, 100];

export function DataTablePagination({
    meta,
    links,
    perPage,
    onPerPageChange,
}: {
    meta: DataTableMeta;
    links: DataTableLink[];
    perPage: number;
    onPerPageChange: (value: number) => void;
}) {
    const previous = links[0];
    const next = links[links.length - 1];
    const pageLinks = links.slice(1, -1);

    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-muted-foreground flex flex-wrap items-center gap-3 text-sm">
                <span>
                    {meta.total === 0
                        ? 'No results'
                        : `Showing ${meta.from ?? 0}–${meta.to ?? 0} of ${meta.total}`}
                </span>
                <div className="flex items-center gap-2">
                    <span>Rows</span>
                    <Select
                        value={String(perPage)}
                        onValueChange={(value) =>
                            onPerPageChange(Number(value))
                        }
                    >
                        <SelectTrigger className="h-8 w-[4.5rem]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {PER_PAGE_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option}
                                    value={String(option)}
                                >
                                    {option}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {meta.last_page > 1 ? (
                <div className="flex flex-wrap items-center justify-end gap-1">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!previous?.url}
                        asChild={Boolean(previous?.url)}
                    >
                        {previous?.url ? (
                            <Link href={previous.url} preserveScroll>
                                Previous
                            </Link>
                        ) : (
                            <span>Previous</span>
                        )}
                    </Button>
                    {pageLinks.map((link, index) => {
                        const label = link.label
                            .replace('&laquo;', '«')
                            .replace('&raquo;', '»');

                        if (!link.url) {
                            return (
                                <Button
                                    key={`${label}-${index}`}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    disabled
                                >
                                    {label}
                                </Button>
                            );
                        }

                        return (
                            <Button
                                key={`${label}-${index}`}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                asChild
                            >
                                <Link href={link.url} preserveScroll>
                                    {label}
                                </Link>
                            </Button>
                        );
                    })}
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!next?.url}
                        asChild={Boolean(next?.url)}
                    >
                        {next?.url ? (
                            <Link href={next.url} preserveScroll>
                                Next
                            </Link>
                        ) : (
                            <span>Next</span>
                        )}
                    </Button>
                </div>
            ) : null}
        </div>
    );
}
