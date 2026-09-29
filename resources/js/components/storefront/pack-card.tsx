import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import type { StorefrontPack } from '@/types/storefront';
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
    const currency = pack.currency ?? 'AUD';
    const imageSrc = pack.image || FALLBACK_IMAGE;
    const hasStockLeft =
        pack.stockLeft !== null && pack.stockLeft !== undefined;

    return (
        <article
            className={cn(
                'border-border bg-card hover:border-primary/40 flex h-full flex-col overflow-hidden rounded-xl border transition',
                className,
            )}
        >
            <PackCardLink href={href}>
                <div className="bg-muted relative aspect-3/2 overflow-hidden">
                    {pack.savings ? (
                        <span className="bg-primary text-primary-foreground absolute top-3 left-3 z-10 rounded-md px-2.5 py-1 text-xs font-bold tracking-wide uppercase shadow-sm">
                            Save {currency} {pack.savings}
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
                        <span className="text-foreground absolute right-3 bottom-3 z-10 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold shadow-sm backdrop-blur-sm">
                            {pack.stockLeft} left
                        </span>
                    ) : null}
                </div>
                <div className="flex flex-1 flex-col gap-1 p-4 pb-3">
                    <p className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                        {pack.eyebrow}
                    </p>
                    <h3 className="text-foreground text-lg leading-tight font-bold">
                        {pack.name}
                    </h3>
                    <p className="text-muted-foreground line-clamp-2 text-sm">
                        {pack.description}
                    </p>
                </div>
            </PackCardLink>
            <div className="mx-4 flex items-end justify-between gap-3 border-t py-4">
                <div className="min-w-0">
                    <p className="text-foreground text-2xl leading-none font-bold tabular-nums">
                        {currency} {pack.price}
                    </p>
                    <p className="text-muted-foreground mt-1 text-xs">
                        inc. tax
                    </p>
                </div>
                <Button
                    type="button"
                    size="sm"
                    className="h-9 shrink-0 rounded-full px-4 text-xs font-bold tracking-wide uppercase"
                    disabled={disabled || buying}
                    onClick={onBuyNow}
                >
                    <Plus className="size-3.5" />
                    {buying ? 'Adding...' : 'Buy now'}
                </Button>
            </div>
        </article>
    );
}
