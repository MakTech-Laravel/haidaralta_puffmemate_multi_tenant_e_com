import { Head } from '@inertiajs/react';
import { ContactForm } from '@/components/storefront/contact/contact-form';
import { ContactHero } from '@/components/storefront/contact/contact-hero';
import { ContactInfo } from '@/components/storefront/contact/contact-info';

export default function StorefrontContact() {
    return (
        <>
            <Head title="Contact" />

            <ContactHero />

            <section className="bg-muted/50 py-10 sm:py-12 lg:py-14">
                <div className="container mx-auto px-4 sm:px-6">
                    <div className="border-border bg-background overflow-hidden rounded-xl border shadow-lg">
                        <div className="grid items-stretch lg:grid-cols-[minmax(0,20rem)_1fr] xl:grid-cols-[24rem_1fr]">
                            <ContactInfo className="h-full" />
                            <ContactForm className="h-full" />
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
