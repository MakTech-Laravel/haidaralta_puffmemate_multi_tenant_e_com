import type { LucideIcon } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function StatCard({
    className,
    hint,
    icon: Icon,
    label,
    value,
    valueClassName,
}: {
    className?: string;
    hint?: string;
    icon?: LucideIcon;
    label: string;
    value: string;
    valueClassName?: string;
}) {
    return (
        <Card className={cn('gap-3 py-5 shadow-none', className)}>
            <CardHeader className="flex flex-row items-start justify-between gap-2 px-5">
                <CardDescription className="text-sm font-medium">
                    {label}
                </CardDescription>
                {Icon ? (
                    <span className="bg-primary-tint text-primary flex size-9 shrink-0 items-center justify-center rounded-xl"><Icon className="size-4" /></span>
                ) : null}
            </CardHeader>
            <CardContent className="px-5">
                <p
                    className={cn(
                        'text-3xl font-semibold tracking-tight tabular-nums',
                        valueClassName,
                    )}
                >
                    {value}
                </p>
                {hint ? (
                    <p className="text-muted-foreground mt-1 text-xs">{hint}</p>
                ) : null}
            </CardContent>
        </Card>
    );
}
