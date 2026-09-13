import { faqHero } from '@/lib/storefront-faq';
import { cn } from '@/lib/utils';

type FaqHeroProps = {
    className?: string;
};

export function FaqHero({ className }: FaqHeroProps) {
    return (
        <section className={cn('bg-background py-12 sm:py-16', className)}>
            <div className="mx-auto container max-w-3xl px-4 text-center sm:px-6">
                <p className="bg-muted text-foreground inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[0.7rem] font-bold tracking-wide uppercase sm:text-xs">
                    <span
                        className="bg-primary size-1.5 shrink-0 rounded-full"
                        aria-hidden
                    />
                    {faqHero.badge}
                </p>
                <h1 className="text-foreground mt-6 text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                    {faqHero.title}
                </h1>
                <p className="text-muted-foreground mx-auto mt-4 max-w-2xl text-sm leading-relaxed sm:text-base">
                    {faqHero.subtitle}
                </p>
            </div>
        </section>
    );
}
