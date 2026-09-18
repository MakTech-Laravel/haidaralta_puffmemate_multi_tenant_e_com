import { faqHero } from '@/lib/storefront-faq';
import { cn } from '@/lib/utils';

type FaqHeroProps = {
    className?: string;
};

export function FaqHero({ className }: FaqHeroProps) {
    return (
        <section className={cn('bg-background py-12 sm:py-16', className)}>
            <div className="container mx-auto max-w-4xl px-4 text-center sm:px-6">
                <p className="bg-muted text-foreground inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[0.7rem] font-bold tracking-wide uppercase sm:text-xs">
                    <span
                        className="bg-primary size-1.5 shrink-0 rounded-full"
                        aria-hidden
                    />
                    {faqHero.badge}
                </p>
                <h1 className="text-foreground font-inter mt-6 text-3xl font-bold tracking-tight sm:text-4xl lg:text-6xl">
                    {faqHero.title}
                </h1>
                <p className="text-muted-foreground font-inter mx-auto mt-4 max-w-2xl text-sm leading-relaxed font-normal sm:text-lg">
                    {faqHero.subtitle}
                </p>
            </div>
        </section>
    );
}
