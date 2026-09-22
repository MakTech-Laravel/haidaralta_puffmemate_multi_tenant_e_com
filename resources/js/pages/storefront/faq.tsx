import { Head } from '@inertiajs/react';
import { FaqCategories } from '@/components/storefront/faq/faq-categories';
import { FaqHero } from '@/components/storefront/faq/faq-hero';

type FaqPayload = {
    hero: { badge: string; title: string; subtitle: string };
    categories: Array<{
        id: string;
        title: string;
        icon: string;
        items: Array<{
            id: string;
            question: string;
            answer: string;
            defaultOpen?: boolean;
        }>;
    }>;
};

type CmsPageProp = {
    slug: string;
    title: string;
    meta_title: string | null;
    meta_description: string | null;
    payload: FaqPayload;
};

export default function StorefrontFaq({ cmsPage }: { cmsPage: CmsPageProp }) {
    return (
        <>
            <Head title={cmsPage.meta_title || cmsPage.title}>
                {cmsPage.meta_description ? (
                    <meta
                        name="description"
                        content={cmsPage.meta_description}
                    />
                ) : null}
            </Head>

            <FaqHero data={cmsPage.payload.hero} />
            <FaqCategories categories={cmsPage.payload.categories} />
        </>
    );
}
