import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

export function PortalBrand({
    eyebrow,
    subtitle,
    tone = 'default',
}: {
    eyebrow?: string;
    subtitle?: string;
    tone?: 'default' | 'inverse';
}) {
    const { name } = usePage().props;
    const inverse = tone === 'inverse';

    return (
        <>
            <div
                className={cn(
                    'flex aspect-square size-8 items-center justify-center rounded-md',
                    inverse
                        ? 'text-foreground bg-white'
                        : 'bg-sidebar-primary text-sidebar-primary-foreground',
                )}
            >
                <AppLogoIcon className="size-5 fill-current" />
            </div>
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
                    {name}
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
