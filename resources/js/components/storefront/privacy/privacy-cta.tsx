import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { privacyPage } from '@/lib/storefront-privacy';
import { cn } from '@/lib/utils';
import { contact, faqs } from '@/routes';

type PrivacyCtaProps = {
    className?: string;
};

export function PrivacyCta({ className }: PrivacyCtaProps) {
    return (
        <section className={cn('bg-background pb-14 sm:pb-16', className)}>
            <div className="mx-auto container max-w-4xl px-4 sm:px-6">
                <div className="bg-muted/70 flex flex-col gap-5 rounded-xl px-5 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-7">
                    <p className="text-foreground max-w-xl text-sm leading-relaxed sm:text-base">
                        {privacyPage.cta.body}
                    </p>
                    <div className="flex shrink-0 flex-wrap gap-3">
                        <Button
                            asChild
                            className="bg-primary text-primary-foreground hover:bg-primary/90 h-10 rounded-full px-5 font-semibold"
                        >
                            <Link href={contact()} prefetch>
                                {privacyPage.cta.primaryLabel}
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="border-foreground text-foreground hover:bg-foreground hover:text-background h-10 rounded-full px-5 font-semibold"
                        >
                            <Link href={faqs()} prefetch>
                                {privacyPage.cta.secondaryLabel}
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
