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
        <Card className={cn('gap-3 py-4 shadow-none', className)}>
            <CardHeader className="flex flex-row items-start justify-between gap-2 px-4">
                <CardDescription className="text-xs font-medium tracking-wide uppercase">
                    {label}
                </CardDescription>
                {Icon ? (
                    <Icon className="text-muted-foreground size-4 shrink-0" />
                ) : null}
            </CardHeader>
            <CardContent className="px-4">
                <p
                    className={cn(
                        'text-2xl font-semibold tracking-tight tabular-nums',
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
