import { Head } from '@inertiajs/react';
import { PrivacyCta } from '@/components/storefront/privacy/privacy-cta';
import { PrivacyIntro } from '@/components/storefront/privacy/privacy-intro';
import { PrivacySections } from '@/components/storefront/privacy/privacy-sections';

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
