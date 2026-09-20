import type { LucideIcon } from 'lucide-react';

export function DriverEmptyState({
    icon: Icon,
    title,
    description,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
}) {
    return (
        <div className="border-border bg-card flex flex-col items-center justify-center rounded-xl border px-6 py-16 text-center">
            <Icon
                className="text-muted-foreground mb-4 size-10"
                strokeWidth={1.5}
                aria-hidden
            />
            <h2 className="text-foreground text-lg font-semibold tracking-tight">
                {title}
            </h2>
            <p className="text-muted-foreground mt-2 max-w-sm text-sm leading-relaxed">
                {description}
            </p>
        </div>
    );
}
