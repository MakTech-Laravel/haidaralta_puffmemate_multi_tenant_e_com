import { PackageCheck } from 'lucide-react';
import { PackBuyControls } from '@/components/storefront/packs-saves/pack-buy-controls';
import { PackInsideList } from '@/components/storefront/packs-saves/pack-inside-list';
import {
    formatStorefrontAud,
    type StorefrontPackDetail,
} from '@/types/storefront';
import { cn } from '@/lib/utils';

const PAYMENT_OPTIONS = ['POS Machine', 'Cash Accepted'];

type PackDetailSummaryProps = {
    pack: StorefrontPackDetail;
    className?: string;
};

export function PackDetailSummary({ pack, className }: PackDetailSummaryProps) {
    const subtitle = [pack.eyebrow, pack.branchName]
        .filter(Boolean)
        .join(' · ');

    return (
        <section
            className={cn(
                'border-border bg-card flex flex-col gap-5 rounded-xl border p-5 shadow-sm sm:p-6',
                className,
            )}
        >
            <div>
                <h1 className="text-foreground text-2xl leading-tight font-extrabold tracking-tight sm:text-3xl">
                    {pack.name}
                </h1>
                {subtitle ? (
                    <p className="text-muted-foreground mt-1 text-sm">
                        {subtitle}
                    </p>
                ) : null}
                {pack.description ? (
                    <p className="text-muted-foreground mt-3 text-sm leading-relaxed">
                        {pack.description}
                    </p>
                ) : null}
            </div>

            <PackInsideList
                title="What's inside the value box"
                items={pack.items}
            />

            <div className="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                <p className="text-primary text-3xl font-extrabold tabular-nums">
                    {formatStorefrontAud(pack.price)}
                </p>
                {pack.savings ? (
                    <span className="text-muted-foreground text-sm line-through tabular-nums">
                        {formatStorefrontAud(pack.regularPrice)} RRP
                    </span>
                ) : null}
            </div>

            <PackBuyControls
                stockLeft={pack.stockLeft}
                buyLabel={`Buy now`}
            />

            <div className="flex flex-wrap items-start justify-between gap-3 text-xs">
                <p className="text-muted-foreground font-medium">
                    Maximum {pack.stockLeft ?? 0} packs available for this
                    offer
                </p>
                <p className="text-foreground inline-flex items-center gap-1.5 font-semibold">
                    <PackageCheck className="size-4" />
                    Discreet tamper-evident bag
                </p>
            </div>

            <div className="bg-muted/60 rounded-lg p-3">
                <p className="text-muted-foreground text-xs font-medium">
                    Payment options at door
                </p>
                <div className="mt-2 flex flex-wrap gap-2">
                    {PAYMENT_OPTIONS.map((option) => (
                        <span
                            key={option}
                            className="border-border bg-card text-foreground rounded-md border px-2.5 py-1 text-xs font-semibold"
                        >
                            {option}
                        </span>
                    ))}
                </div>
            </div>
        </section>
    );
}
