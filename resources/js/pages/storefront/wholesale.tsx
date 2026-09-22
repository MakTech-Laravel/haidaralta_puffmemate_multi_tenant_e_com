import { Head } from '@inertiajs/react';
import { WholesaleForm } from '@/components/storefront/wholesale/wholesale-form';
import { WholesaleInfo } from '@/components/storefront/wholesale/wholesale-info';
import { WholesaleIntro } from '@/components/storefront/wholesale/wholesale-intro';

type WholesalePayload = {
    intro: {
        title: string;
        body: string;
        benefits: Array<{ title: string; description: string }>;
    };
    inquiry: { title: string; subtitle: string };
    form: Record<string, string>;
};

type CmsPageProp = {
    slug: string;
    title: string;
    meta_title: string | null;
    meta_description: string | null;
    payload: WholesalePayload;
};

export default function StorefrontWholesale({
    cmsPage,
}: {
    cmsPage: CmsPageProp;
}) {
    const { intro, inquiry, form } = cmsPage.payload;

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

            <WholesaleIntro data={intro} />

            <section className="bg-muted/50 py-10 sm:py-12 lg:py-14">
                <div className="container mx-auto px-4 sm:px-6">
                    <div className="border-border bg-background overflow-hidden rounded-xl border shadow-lg">
                        <div className="grid items-stretch lg:grid-cols-[minmax(0,20rem)_1fr] xl:grid-cols-[24rem_1fr]">
                            <WholesaleInfo
                                className="h-full"
                                title={inquiry.title}
                                subtitle={inquiry.subtitle}
                            />
                            <WholesaleForm className="h-full" copy={form} />
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
