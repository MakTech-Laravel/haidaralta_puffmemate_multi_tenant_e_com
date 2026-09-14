import { Head } from '@inertiajs/react';
import { FaqCategories } from '@/components/storefront/faq/faq-categories';
import { FaqHero } from '@/components/storefront/faq/faq-hero';

export default function StorefrontFaq() {
    return (
        <>
            <Head title="FAQs" />

            <FaqHero />
            <FaqCategories />
        </>
    );
}
