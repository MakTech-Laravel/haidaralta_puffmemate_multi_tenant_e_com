import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export type QuickActionTone = 'primary' | 'warning' | 'quiet' | 'muted';

const toneClasses: Record<QuickActionTone, string> = {
    primary: 'bg-primary-tint text-primary',
    warning: 'bg-warning-tint text-warning',
    quiet: 'bg-secondary text-secondary-foreground',
    muted: 'bg-muted text-muted-foreground',
};

export function QuickActionTile({
    href,
    icon: Icon,
    label,
    hint,
    tone = 'primary',
}: {
    href?: string;
    icon: LucideIcon;
    label: string;
    hint: string;
    tone?: QuickActionTone;
}) {
    const className = cn(
        'border-border flex items-center gap-3 rounded-xl border px-3 py-3 text-left transition-colors',
        href ? 'hover:bg-accent' : 'cursor-default opacity-80',
    );

    const inner = (
        <>
            <span
                className={cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-xl',
                    toneClasses[tone],
                )}
            >
                <Icon className="size-4" aria-hidden />
            </span>
            <span className="min-w-0">
                <span className="text-foreground block text-sm font-semibold">
                    {label}
                </span>
                <span className="text-muted-foreground block truncate text-xs">
                    {hint}
                </span>
            </span>
        </>
    );

    if (href) {
        return (
            <Link href={href} prefetch className={className}>
                {inner}
            </Link>
        );
    }

    return (
        <div className={className} aria-disabled="true">
            {inner}
        </div>
    );
}
