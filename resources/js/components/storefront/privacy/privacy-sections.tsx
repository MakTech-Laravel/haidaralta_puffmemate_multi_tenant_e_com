import { PrivacySection } from '@/components/storefront/privacy/privacy-section';
import { privacySections } from '@/lib/storefront-privacy';
import { cn } from '@/lib/utils';

type PrivacySectionsProps = {
    className?: string;
};

export function PrivacySections({ className }: PrivacySectionsProps) {
    return (
        <section className={cn('bg-background pb-10 sm:pb-12', className)}>
            <div className="mx-auto container max-w-4xl space-y-10 px-4 sm:space-y-12 sm:px-6">
                {privacySections.map((section) => (
                    <PrivacySection key={section.id} section={section} />
                ))}
            </div>
        </section>
    );
}
