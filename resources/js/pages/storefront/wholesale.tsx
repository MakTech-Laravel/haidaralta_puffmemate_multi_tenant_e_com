import { Head } from '@inertiajs/react';
import { WholesaleForm } from '@/components/storefront/wholesale/wholesale-form';
import { WholesaleInfo } from '@/components/storefront/wholesale/wholesale-info';
import { WholesaleIntro } from '@/components/storefront/wholesale/wholesale-intro';

export default function StorefrontWholesale() {
    return (
        <>
            <Head title="Wholesale" />

            <WholesaleIntro />

            <section className="bg-muted/50 py-10 sm:py-12 lg:py-14">
                <div className="container mx-auto px-4 sm:px-6">
                    <div className="border-border bg-background overflow-hidden rounded-xl border shadow-lg">
                        <div className="grid items-stretch lg:grid-cols-[minmax(0,20rem)_1fr] xl:grid-cols-[24rem_1fr]">
                            <WholesaleInfo className="h-full" />
                            <WholesaleForm className="h-full" />
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
