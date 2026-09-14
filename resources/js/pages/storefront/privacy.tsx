import { Head } from '@inertiajs/react';
import { PrivacyCta } from '@/components/storefront/privacy-cta';
import { PrivacyIntro } from '@/components/storefront/privacy-intro';
import { PrivacySections } from '@/components/storefront/privacy-sections';

export default function StorefrontPrivacy() {
    return (
        <>
            <Head title="Privacy Policy" />

            <PrivacyIntro />
            <PrivacySections />
            <PrivacyCta />
        </>
    );
}
