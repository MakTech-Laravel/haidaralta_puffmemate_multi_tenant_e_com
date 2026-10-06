import { Head } from '@inertiajs/react';
import { ContactForm } from '@/components/storefront/contact/contact-form';
import { ContactHero } from '@/components/storefront/contact/contact-hero';
import { ContactInfo } from '@/components/storefront/contact/contact-info';

type ContactPayload = {
    hero: { title: string; subtitle: string; image: string };
    info: { title: string; subtitle: string };
    form: Record<string, string>;
};

type CmsPageProp = {
    slug: string;
    title: string;
    meta_title: string | null;
    meta_description: string | null;
    payload: ContactPayload;
};

export default function StorefrontContact({
    cmsPage,
}: {
    cmsPage: CmsPageProp;
}) {
    const { hero, info, form } = cmsPage.payload;

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

            <ContactHero data={hero} />

            <section className="bg-muted/50 py-10 sm:py-12 lg:py-14">
                <div className="container mx-auto px-4 sm:px-6">
                    <div className="border-border bg-background overflow-hidden rounded-none border shadow-lg">
                        <div className="grid items-stretch lg:grid-cols-[minmax(0,20rem)_1fr] xl:grid-cols-[24rem_1fr]">
                            <ContactInfo
                                className="h-full"
                                title={info.title}
                                subtitle={info.subtitle}
                            />
                            <ContactForm className="h-full" copy={form} />
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
