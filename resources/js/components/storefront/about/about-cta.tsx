import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { shop } from '@/routes';

type AboutCtaData = {
    eyebrow: string;
    heading: string;
    body: string;
    primaryLabel: string;
    secondaryLabel: string;
};

type AboutCtaProps = {
    data: AboutCtaData;
    className?: string;
};

export function AboutCta({ data, className }: AboutCtaProps) {
    return (
        <section className={cn('bg-ink-surface py-16 sm:py-20', className)}>
            <div className="container mx-auto max-w-2xl px-4 text-center sm:px-6">
                <p className="text-ink-surface-foreground/85 text-xs font-semibold tracking-[0.14em] uppercase">
                    {data.eyebrow}
                </p>
                <h2 className="text-ink-surface-foreground mt-3 text-3xl font-semibold tracking-tight sm:text-5xl">
                    {data.heading}
                </h2>
                <p className="text-ink-surface-foreground/85 font-inter mt-4 text-sm leading-relaxed font-normal sm:text-lg">
                    {data.body}
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button
                        asChild
                        size="lg"
                        className="bg-background text-foreground hover:bg-background/90 font-inter h-12 px-7 font-semibold"
                    >
                        <Link href={shop()} prefetch>
                            {data.primaryLabel}
                            <ArrowRight className="size-4" aria-hidden />
                        </Link>
                    </Button>
                    <Button
                        asChild
                        size="lg"
                        variant="outline"
                        className="border-ink-surface-foreground text-ink-surface-foreground hover:bg-ink-surface-foreground/10 font-inter h-12 border bg-transparent px-7 font-semibold"
                    >
                        <Link href={shop()} prefetch>
                            {data.secondaryLabel}
                        </Link>
                    </Button>
                </div>
            </div>
        </section>
    );
}
