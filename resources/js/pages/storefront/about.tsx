import { Head } from '@inertiajs/react';
import { AboutCta } from '@/components/storefront/about/about-cta';
import { AboutDelivery } from '@/components/storefront/about/about-delivery';
import { AboutHero } from '@/components/storefront/about/about-hero';
import { AboutHowItWorks } from '@/components/storefront/about/about-how-it-works';
import { AboutMultiBranch } from '@/components/storefront/about/about-multi-branch';
import { AboutWhoWeAre } from '@/components/storefront/about/about-who-we-are';

type CmsPageProp = {
    slug: string;
    title: string;
    meta_title: string | null;
    meta_description: string | null;
    payload: {
        hero: { title: string; subtitle: string; image: string };
        who_we_are: {
            eyebrow: string;
            heading: string;
            body: string;
            mediaImage: string;
            mediaTitle: string;
            mediaCaption: string;
            features: { title: string; description: string }[];
        };
        how_it_works: {
            eyebrow: string;
            heading: string;
            body: string;
            steps: {
                number: string;
                title: string;
                description: string;
                icon: string;
            }[];
        };
        multi_branch: {
            eyebrow: string;
            heading: string;
            body: string;
            promise: string;
            factors: {
                number: string;
                title: string;
                description: string;
            }[];
        };
        delivery: {
            eyebrow: string;
            heading: string;
            paragraphs: string[];
            statuses: {
                label: string;
                state: 'done' | 'active' | 'pending';
            }[];
        };
        cta: {
            eyebrow: string;
            heading: string;
            body: string;
            primaryLabel: string;
            secondaryLabel: string;
        };
    };
};

export default function StorefrontAbout({
    cmsPage,
}: {
    cmsPage: CmsPageProp;
}) {
    const p = cmsPage.payload;

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

            <AboutHero data={p.hero} />
            <AboutWhoWeAre data={p.who_we_are} />
            <AboutHowItWorks data={p.how_it_works} />
            <AboutMultiBranch data={p.multi_branch} />
            <AboutDelivery data={p.delivery} />
            <AboutCta data={p.cta} />
        </>
    );
}
