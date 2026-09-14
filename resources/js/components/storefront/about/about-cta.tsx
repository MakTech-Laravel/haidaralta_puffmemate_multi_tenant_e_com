import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { aboutCta } from '@/lib/storefront-about';
import { cn } from '@/lib/utils';
import { shop } from '@/routes';

type AboutCtaProps = {
    className?: string;
};

export function AboutCta({ className }: AboutCtaProps) {
    return (
        <section className={cn('bg-primary py-16 sm:py-20', className)}>
            <div className="mx-auto container max-w-2xl px-4 text-center sm:px-6">
                <p className="text-primary-foreground/85 text-xs font-semibold tracking-[0.14em] uppercase">
                    {aboutCta.eyebrow}
                </p>
                <h2 className="text-primary-foreground mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                    {aboutCta.heading}
                </h2>
                <p className="text-primary-foreground/85 mt-4 text-sm leading-relaxed sm:text-lg font-inter font-normal">
                    {aboutCta.body}
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button
                        asChild
                        size="lg"
                        className="bg-background text-primary hover:bg-background/90 h-11 rounded-md px-6 font-bold font-inter"
                    >
                        <Link href={shop()} prefetch>
                            {aboutCta.primaryLabel}
                            <ArrowRight className="size-4" aria-hidden />
                        </Link>
                    </Button>
                    <Button
                        asChild
                        size="lg"
                        variant="outline"
                        className="border-primary-foreground text-primary-foreground hover:bg-primary-foreground/10 h-11 rounded-md border bg-transparent px-6 font-bold font-inter"
                    >
                        <Link href={shop()} prefetch>
                            {aboutCta.secondaryLabel}
                        </Link>
                    </Button>
                </div>
            </div>
        </section>
    );
}
