import type { VariantProps } from 'class-variance-authority';
import type { badgeVariants } from '@/components/ui/badge';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

/** Status badges using app.css theme tokens only. */
export function backupStatusBadgeVariant(status: string): BadgeVariant {
    switch (status) {
        case 'completed':
            return 'default';
        case 'running':
        case 'pending':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
}

export function backupTypeBadgeVariant(type: string): BadgeVariant {
    return type === 'import' ? 'secondary' : 'outline';
}

export function formatBytes(bytes: number | null): string {
    if (bytes === null || bytes <= 0) {
        return '—';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${value.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
}
