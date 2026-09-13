import { Head } from '@inertiajs/react';
import { FaqCategories } from '@/components/storefront/faq-categories';
import { FaqHero } from '@/components/storefront/faq-hero';

export default function StorefrontFaq() {
    return (
        <>
            <Head title="FAQs" />

            <FaqHero />
            <FaqCategories />
        </>
    );
}
