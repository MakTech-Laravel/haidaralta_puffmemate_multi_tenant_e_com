import type { ReactNode } from 'react';

export function ShiftPageHeader({
    kicker,
    title,
    description,
    actions,
}: {
    kicker: string;
    title: string;
    description: string;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="flex min-w-0 flex-col gap-1">
                <p className="text-muted-foreground text-[11px] font-medium tracking-[0.18em] uppercase">
                    {kicker}
                </p>
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                <p className="text-muted-foreground max-w-xl text-sm">
                    {description}
                </p>
            </div>
            {actions ? (
                <div className="flex flex-wrap items-center gap-2">{actions}</div>
            ) : null}
        </div>
    );
}
