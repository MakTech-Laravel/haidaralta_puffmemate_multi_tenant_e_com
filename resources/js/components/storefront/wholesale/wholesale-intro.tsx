import { wholesaleIntro } from '@/lib/storefront-wholesale';
import { cn } from '@/lib/utils';

type WholesaleIntroProps = {
    className?: string;
};

export function WholesaleIntro({ className }: WholesaleIntroProps) {
    return (
        <section className={cn('bg-background py-12 sm:py-16', className)}>
            <div className="mx-auto container px-4 sm:px-6">
                <div className="max-w-3xl">
                    <h1 className="text-foreground text-2xl font-bold tracking-tight uppercase sm:text-3xl lg:text-4xl">
                        {wholesaleIntro.title}
                    </h1>
                    <p className="text-muted-foreground mt-4 text-sm leading-relaxed sm:text-base">
                        {wholesaleIntro.body}
                    </p>
                </div>

                <ul className="mt-10 grid gap-8 sm:grid-cols-2 lg:mt-12 lg:grid-cols-4 lg:gap-6">
                    {wholesaleIntro.benefits.map((benefit) => (
                        <li key={benefit.title}>
                            <h2 className="text-foreground text-base font-bold tracking-tight sm:text-lg">
                                {benefit.title}
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                {benefit.description}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
