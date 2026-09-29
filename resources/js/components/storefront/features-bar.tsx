import type { StorefrontFeature } from '@/types/storefront';
import { cn } from '@/lib/utils';

type FeaturesBarProps = {
    features: StorefrontFeature[];
    className?: string;
};

export function FeaturesBar({ features, className }: FeaturesBarProps) {
    return (
        <section className={cn('', className)}>
            <div className="container mx-auto px-4 sm:px-6">
                <ul className="grid sm:grid-cols-2 lg:grid-cols-4">
                    {features.map((feature, index) => (
                        <li
                            key={feature.title}
                            className={cn(
                                'relative flex flex-col items-center justify-center gap-1 px-6 py-6 text-center sm:py-12',
                                index > 0 &&
                                    'before:bg-border before:absolute before:top-1/2 before:left-0 before:hidden before:h-12 before:w-px before:-translate-y-1/2 lg:before:block',
                            )}
                        >
                            <h3 className="text-foreground text-base font-bold tracking-tight">
                                {feature.title}
                            </h3>
                            <p className="text-muted-foreground text-sm leading-snug">
                                {feature.description}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
