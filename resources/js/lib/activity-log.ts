import type { VariantProps } from 'class-variance-authority';
import type { badgeVariants } from '@/components/ui/badge';

type BadgeVariant = NonNullable<
    VariantProps<typeof badgeVariants>['variant']
>;

/**
 * Map Spatie activity events to theme badge variants from app.css tokens.
 * created → primary red fill; updated → secondary gray; deleted → destructive red.
 */
export function eventBadgeVariant(event: string | null): BadgeVariant {
    switch (event) {
        case 'created':
            return 'default';
        case 'updated':
            return 'secondary';
        case 'deleted':
            return 'destructive';
        default:
            return 'outline';
    }
}
