import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { PackDetailSummary } from '@/components/storefront/pack-detail/pack-detail-summary';
import { PackSpecCards } from '@/components/storefront/pack-detail/pack-spec-cards';
import { PackTrustNote } from '@/components/storefront/pack-detail/pack-trust-note';
import { RelatedPacks } from '@/components/storefront/pack-detail/related-packs';
import { ProductGallery } from '@/components/storefront/product/product-gallery';
import { storefrontFeatures } from '@/lib/storefront-features';
import type { StorefrontPack, StorefrontPackDetail } from '@/types/storefront';

type PackPageProps = {
    pack: StorefrontPackDetail;
    relatedPacks: StorefrontPack[];
    backHref: string;
    backLabel: string;
};

export default function StorefrontPackPage({
    pack,
    relatedPacks,
    backHref,
    backLabel,
}: PackPageProps) {
    return (
        <>
            <Head title={pack.name} />

            <div className="container mx-auto flex flex-col gap-10 px-4 py-8 sm:px-6 sm:py-10">
                <div>
                    <Link
                        href={backHref}
                        className="text-primary mb-4 inline-flex items-center gap-1.5 text-xs font-medium hover:underline"
                    >
                        <ArrowLeft className="size-3.5" />
                        {backLabel}
                    </Link>

                    <div className="bg-muted/60 grid min-w-0 gap-6 rounded-2xl p-4 sm:p-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:gap-8 lg:p-8">
                        <div className="flex min-w-0 flex-col gap-4">
                            <ProductGallery
                                key={pack.id}
                                images={pack.images}
                                alt={pack.name}
                                fallbackImage="/images/storefront/logo.png"
                                mainClassName="aspect-4/3 rounded-xl"
                            />
                            <PackTrustNote />
                        </div>
                        <PackDetailSummary
                            key={pack.id}
                            className="min-w-0 self-start"
                            pack={pack}
                        />
                    </div>
                </div>

                <PackSpecCards pack={pack} />

                <RelatedPacks packs={relatedPacks} />
            </div>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
