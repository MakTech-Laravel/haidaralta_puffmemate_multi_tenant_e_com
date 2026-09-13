import type { StorefrontFeature } from '@/lib/storefront-demo';
import { cn } from '@/lib/utils';

type FeaturesBarProps = {
    features: StorefrontFeature[];
    className?: string;
};

export function FeaturesBar({ features, className }: FeaturesBarProps) {
    return (
        <section className={cn('bg-muted/60 border-border border-y', className)}>
            <div className="mx-auto grid container gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:gap-6 lg:py-12">
                {features.map((feature) => (
                    <div key={feature.title} className="flex flex-col gap-1.5">
                        <h3 className="text-foreground text-sm font-semibold tracking-tight">
                            {feature.title}
                        </h3>
                        <p className="text-muted-foreground text-sm leading-relaxed">
                            {feature.description}
                        </p>
                    </div>
                ))}
            </div>
        </section>
    );
}
