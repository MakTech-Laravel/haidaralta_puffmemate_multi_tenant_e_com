import { Head } from '@inertiajs/react';
import { AboutCta } from '@/components/storefront/about-cta';
import { AboutDelivery } from '@/components/storefront/about-delivery';
import { AboutHero } from '@/components/storefront/about-hero';
import { AboutHowItWorks } from '@/components/storefront/about-how-it-works';
import { AboutMultiBranch } from '@/components/storefront/about-multi-branch';
import { AboutWhoWeAre } from '@/components/storefront/about-who-we-are';

export default function StorefrontAbout() {
    return (
        <>
            <Head title="About" />

            <AboutHero />
            <AboutWhoWeAre />
            <AboutHowItWorks />
            <AboutMultiBranch />
            <AboutDelivery />
            <AboutCta />
        </>
    );
}
