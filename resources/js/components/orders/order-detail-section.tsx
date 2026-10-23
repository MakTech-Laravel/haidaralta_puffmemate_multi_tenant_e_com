import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function SectionCard({
    icon: Icon,
    title,
    action,
    children,
    className,
}: {
    icon: LucideIcon;
    title: string;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card className={`border-border/70 gap-4 py-5 ${className ?? ''}`}>
            <CardHeader className="flex flex-row items-center justify-between gap-3 px-5">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Icon
                        className="text-muted-foreground size-4 shrink-0"
                        aria-hidden
                    />
                    {title}
                </CardTitle>
                {action}
            </CardHeader>
            <CardContent className="px-5">{children}</CardContent>
        </Card>
    );
}

export function SummaryRow({
    label,
    children,
}: {
    label: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center justify-between gap-3 text-sm">
            <span className="text-muted-foreground min-w-0 truncate">
                {label}
            </span>
            <span className="shrink-0 text-right font-medium tabular-nums">
                {children}
            </span>
        </div>
    );
}

export function HeaderFact({
    icon: Icon,
    label,
    children,
}: {
    icon: LucideIcon;
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex min-w-0 items-start gap-3">
            <span className="bg-muted text-muted-foreground flex size-9 shrink-0 items-center justify-center rounded-lg">
                <Icon className="size-4" aria-hidden />
            </span>
            <div className="min-w-0">
                <p className="text-muted-foreground text-xs font-medium">
                    {label}
                </p>
                <div className="text-foreground truncate text-sm font-semibold">
                    {children}
                </div>
            </div>
        </div>
    );
}
