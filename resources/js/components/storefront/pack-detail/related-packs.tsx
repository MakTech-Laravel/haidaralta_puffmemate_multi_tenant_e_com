import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PackCard } from '@/components/storefront/pack-card';
import { packsSaves } from '@/routes';
import { show as packShow } from '@/routes/packs-saves';
import type { StorefrontPack } from '@/types/storefront';

type RelatedPacksProps = {
    packs: StorefrontPack[];
    className?: string;
};

export function RelatedPacks({ packs, className }: RelatedPacksProps) {
    if (packs.length === 0) {
        return null;
    }

    return (
        <section className={className}>
            <div className="mb-5 flex items-end justify-between gap-4">
                <h2 className="text-foreground text-xl font-extrabold tracking-tight sm:text-2xl">
                    Related In-Stock Selections
                </h2>
                <Link
                    href={packsSaves.url()}
                    className="text-primary inline-flex items-center gap-1 text-xs font-semibold hover:underline"
                >
                    More
                    <ArrowRight className="size-3.5" />
                </Link>
            </div>
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                {packs.map((pack) => (
                    <PackCard
                        key={pack.id}
                        pack={pack}
                        href={packShow.url({
                            branch: pack.branchSlug,
                            pack: pack.slug,
                        })}
                    />
                ))}
            </div>
        </section>
    );
}
