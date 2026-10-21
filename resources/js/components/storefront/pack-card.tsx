import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { formatStorefrontAud, type StorefrontPack } from '@/types/storefront';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type PackCardProps = {
    pack: StorefrontPack;
    href?: string;
    onBuyNow?: () => void;
    buying?: boolean;
    disabled?: boolean;
    className?: string;
};

function PackCardLink({
    href,
    children,
}: {
    href?: string;
    children: ReactNode;
}) {
    const className = 'flex min-w-0 flex-1 flex-col';

    if (!href) {
        return <div className={className}>{children}</div>;
    }

    return (
        <Link href={href} className={className}>
            {children}
        </Link>
    );
}

export function PackCard({
    pack,
    href,
    onBuyNow,
    buying = false,
    disabled = false,
    className,
}: PackCardProps) {
    const imageSrc = pack.image || FALLBACK_IMAGE;
    const hasStockLeft =
        pack.stockLeft !== null && pack.stockLeft !== undefined;

    return (
        <article
            className={cn(
                'border-border bg-card hover:border-primary/40 @container flex h-full flex-col overflow-hidden rounded-xl border transition',
                className,
            )}
        >
            <PackCardLink href={href}>
                <div className="bg-muted relative aspect-3/2 overflow-hidden">
                    {pack.savings ? (
                        <span className="bg-primary text-primary-foreground absolute top-2 left-2 z-10 max-w-[calc(100%-1rem)] truncate rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase shadow-sm @xs:top-3 @xs:left-3 @xs:px-2.5 @xs:py-1 @xs:text-xs">
                            Save {formatStorefrontAud(pack.savings)}
                        </span>
                    ) : null}
                    <img
                        src={imageSrc}
                        alt={pack.name}
                        loading="lazy"
                        decoding="async"
                        sizes="(max-width: 768px) 50vw, (max-width: 1280px) 33vw, 25vw"
                        className="absolute inset-0 size-full object-cover"
                        onError={(event) => {
                            event.currentTarget.src = FALLBACK_IMAGE;
                        }}
                    />
                    {hasStockLeft ? (
                        <span className="text-foreground absolute right-2 bottom-2 z-10 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-semibold shadow-sm backdrop-blur-sm @xs:right-3 @xs:bottom-3 @xs:px-2.5 @xs:py-1 @xs:text-xs">
                            {pack.stockLeft} left
                        </span>
                    ) : null}
                </div>
                <div className="flex flex-1 flex-col gap-1 p-3 pb-2 @xs:p-4 @xs:pb-3">
                    <p className="text-muted-foreground text-[10px] font-semibold tracking-wider uppercase @xs:text-xs">
                        {pack.eyebrow}
                    </p>
                    <h3 className="text-foreground text-base leading-tight font-bold @xs:text-lg">
                        {pack.name}
                    </h3>
                    <p className="text-muted-foreground line-clamp-2 text-sm">
                        {pack.description}
                    </p>
                </div>
            </PackCardLink>
            <div className="mx-3 flex flex-col gap-2 border-t py-3 @xs:mx-4 @xs:flex-row @xs:items-end @xs:justify-between @xs:gap-3 @xs:py-4">
                <div className="min-w-0">
                    <p className="text-foreground text-xl leading-none font-bold whitespace-nowrap tabular-nums @xs:text-2xl">
                        {formatStorefrontAud(pack.price)}
                    </p>
                    <p className="text-muted-foreground mt-1 text-xs">
                        inc. tax
                    </p>
                </div>
                <Button
                    type="button"
                    size="sm"
                    className="h-9 w-full shrink-0 rounded-full px-4 text-xs font-bold tracking-wide uppercase @xs:w-auto"
                    disabled={disabled || buying}
                    onClick={onBuyNow}
                >
                    <Plus className="size-3.5" />
                    {buying ? 'Redirecting…' : 'Buy now'}
                </Button>
            </div>
        </article>
    );
}
