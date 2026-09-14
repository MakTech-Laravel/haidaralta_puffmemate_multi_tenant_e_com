import { aboutWhoWeAre } from '@/lib/storefront-about';
import { cn } from '@/lib/utils';

type AboutWhoWeAreProps = {
    className?: string;
};

export function AboutWhoWeAre({ className }: AboutWhoWeAreProps) {
    return (
        <section className={cn('bg-background py-16 sm:py-20', className)}>
            <div className="mx-auto container grid items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-14">
                <div>
                    <p className="text-primary text-xs font-semibold tracking-[0.14em] uppercase">
                        {aboutWhoWeAre.eyebrow}
                    </p>
                    <h2 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                        {aboutWhoWeAre.heading}
                    </h2>
                    <p className="text-muted-foreground mt-4 max-w-xl text-sm leading-relaxed sm:text-base font-inter font-normal">
                        {aboutWhoWeAre.body}
                    </p>

                    <ul className="mt-8 grid gap-6 sm:grid-cols-2">
                        {aboutWhoWeAre.features.map((feature) => (
                            <li key={feature.title} className="border border-border p-5 rounded-2xl">
                                <span
                                    className="bg-primary mb-3 block h-1 w-8 rounded-xl"
                                    aria-hidden
                                />
                                <h3 className="text-foreground text-sm font-bold sm:text-base">
                                    {feature.title}
                                </h3>
                                <p className="text-muted-foreground mt-1.5 text-xs leading-relaxed sm:text-sm">
                                    {feature.description}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="bg-muted relative min-h-80 overflow-hidden rounded-2xl sm:min-h-112">
                    <div
                        className="absolute inset-0 bg-cover bg-center"
                        style={{
                            backgroundImage: `url(${aboutWhoWeAre.mediaImage})`,
                        }}
                        role="img"
                        aria-label={aboutWhoWeAre.mediaCaption}
                    />
                    <div
                        className="absolute inset-0 bg-linear-to-t from-foreground/85 via-foreground/25 to-transparent"
                        aria-hidden
                    />
                    <p className="text-primary absolute inset-x-0 bottom-10 p-5 text-base font-semibold tracking-[0.10em] font-inter">
                        {aboutWhoWeAre.mediaTitle}
                    </p>
                    <p className="text-primary-foreground absolute inset-x-0 bottom-0 p-5 text-sm font-semibold sm:p-6 sm:text-lg font-inter">
                        {aboutWhoWeAre.mediaCaption}
                    </p>
                </div>
            </div>
        </section>
    );
}
