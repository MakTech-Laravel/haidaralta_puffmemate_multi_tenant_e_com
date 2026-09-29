import { PackCard } from '@/components/storefront/pack-card';
import type { StorefrontPack } from '@/types/storefront';
import { cn } from '@/lib/utils';

type PacksGridProps = {
    packs: StorefrontPack[];
    updating?: boolean;
    className?: string;
};

export function PacksGrid({
    packs,
    updating = false,
    className,
}: PacksGridProps) {
    return (
        <section className={className}>
            <div className="mb-5 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <h2 className="text-foreground text-2xl font-extrabold tracking-tight">
                    All Packages
                </h2>
                <p className="text-muted-foreground text-xs">
                    Strictly adult 18+. ID check upon courier delivery.
                </p>
            </div>

            <div
                aria-busy={updating}
                className={cn(
                    'transition-opacity',
                    updating && 'pointer-events-none opacity-60',
                )}
            >
                {packs.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No packs match right now. Check back soon or browse the
                        full shop.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                        {packs.map((pack) => (
                            <PackCard key={pack.id} pack={pack} />
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
