import { AppBrandMark } from '@/components/app-brand-mark';
import { useAppBrand } from '@/hooks/use-app-brand';
import { cn } from '@/lib/utils';

export function PortalBrand({
    eyebrow,
    subtitle,
    tone = 'default',
    logoUrl,
}: {
    eyebrow?: string;
    subtitle?: string;
    tone?: 'default' | 'inverse';
    /** Portal-specific mark (e.g. branch logo); falls back to application brand. */
    logoUrl?: string | null;
}) {
    const brand = useAppBrand();
    const inverse = tone === 'inverse';

    return (
        <>
            <AppBrandMark
                logoUrl={logoUrl}
                defaultBackgroundClassName={
                    inverse
                        ? 'bg-white text-foreground'
                        : 'bg-sidebar-primary text-sidebar-primary-foreground'
                }
                iconClassName="size-5 fill-current"
            />
            <div className="ml-1 grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
                {eyebrow ? (
                    <span
                        className={cn(
                            'truncate text-[10px] font-medium tracking-[0.16em] uppercase',
                            inverse
                                ? 'text-primary-foreground/80'
                                : 'text-muted-foreground',
                        )}
                    >
                        {eyebrow}
                    </span>
                ) : null}
                <span
                    className={cn(
                        'truncate leading-tight font-semibold',
                        inverse && 'text-primary-foreground',
                    )}
                >
                    {brand.name}
                </span>
                {subtitle ? (
                    <span
                        className={cn(
                            'truncate text-xs',
                            inverse
                                ? 'text-primary-foreground/80'
                                : 'text-muted-foreground',
                        )}
                    >
                        {subtitle}
                    </span>
                ) : null}
            </div>
        </>
    );
}
