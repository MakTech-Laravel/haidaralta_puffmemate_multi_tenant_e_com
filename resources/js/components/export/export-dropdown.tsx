import { ChevronDown, Download, FileSpreadsheet, FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    buildExportHref,
    type ExportFormat,
    type ExportScope,
} from '@/lib/export-query';
import type { DataTableQueryState } from '@/components/data-table';
import type { QueryParams } from '@/wayfinder';

const formats: { value: ExportFormat; label: string; icon: typeof FileText }[] =
    [
        { value: 'csv', label: 'CSV', icon: FileText },
        { value: 'xlsx', label: 'Excel', icon: FileSpreadsheet },
    ];

const scopes: { value: ExportScope; label: string }[] = [
    { value: 'all', label: 'All records' },
    { value: 'period', label: 'Date range' },
    { value: 'filters', label: 'Applied filters' },
    { value: 'page', label: 'This page' },
];

export function ExportDropdown({
    url,
    query,
    extraQuery,
    hasDateRange = false,
}: {
    url: string;
    query: DataTableQueryState;
    extraQuery?: QueryParams;
    hasDateRange?: boolean;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button type="button" variant="outline">
                    <Download className="size-4" />
                    Export
                    <ChevronDown className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                {formats.map((format) => (
                    <DropdownMenuSub key={format.value}>
                        <DropdownMenuSubTrigger>
                            <format.icon className="size-4" />
                            {format.label}
                        </DropdownMenuSubTrigger>
                        <DropdownMenuSubContent>
                            {scopes.map((scope) => {
                                const disabled =
                                    scope.value === 'period' && !hasDateRange;

                                if (disabled) {
                                    return (
                                        <DropdownMenuItem
                                            key={scope.value}
                                            disabled
                                        >
                                            {scope.label}
                                        </DropdownMenuItem>
                                    );
                                }

                                return (
                                    <DropdownMenuItem
                                        key={scope.value}
                                        asChild
                                    >
                                        <a
                                            href={buildExportHref(
                                                url,
                                                format.value,
                                                scope.value,
                                                query,
                                                extraQuery,
                                            )}
                                        >
                                            {scope.label}
                                        </a>
                                    </DropdownMenuItem>
                                );
                            })}
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
