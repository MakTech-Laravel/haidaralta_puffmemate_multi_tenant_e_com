import { Head } from '@inertiajs/react';
import { SafeHtml } from '@/components/editor/safe-html';
import { PrivacyCopyright } from '@/components/storefront/privacy/privacy-copyright';
import { PrivacyIntro } from '@/components/storefront/privacy/privacy-intro';

type LegalPayload = {
    eyebrow: string;
    intro: string;
    versionLabel: string;
    updatedAt: string;
    applicableEntity: string;
    summaryPoints: Array<{ id: string; title: string; body: string }>;
    body_html: string;
};

type CmsPageProp = {
    slug: string;
    title: string;
    meta_title: string | null;
    meta_description: string | null;
    payload: LegalPayload;
};

export default function StorefrontPrivacy({
    cmsPage,
}: {
    cmsPage: CmsPageProp;
}) {
    const payload = cmsPage.payload;

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

            <PrivacyIntro
                title={cmsPage.title}
                data={{
                    eyebrow: payload.eyebrow,
                    intro: payload.intro,
                    versionLabel: payload.versionLabel,
                    updatedAt: payload.updatedAt,
                    applicableEntity: payload.applicableEntity,
                    summaryPoints: payload.summaryPoints,
                }}
            />

            <section className="bg-background pb-12 sm:pb-16">
                <div className="container mx-auto max-w-3xl px-4 sm:px-6">
                    <SafeHtml html={payload.body_html} />
                </div>
            </section>

            <PrivacyCopyright />
        </>
    );
}
