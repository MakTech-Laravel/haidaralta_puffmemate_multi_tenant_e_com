import { aboutWhoWeAre } from '@/lib/storefront-about';
import { cn } from '@/lib/utils';

type AboutWhoWeAreProps = {
    className?: string;
};

export function AboutWhoWeAre({ className }: AboutWhoWeAreProps) {
    return (
        <section className={cn('bg-background py-16 sm:py-20', className)}>
            <div className="container mx-auto grid items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-14">
                <div>
                    <p className="text-primary text-xs font-semibold tracking-[0.14em] uppercase">
                        {aboutWhoWeAre.eyebrow}
                    </p>
                    <h2 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                        {aboutWhoWeAre.heading}
                    </h2>
                    <p className="text-muted-foreground font-inter mt-4 max-w-xl text-sm leading-relaxed font-normal sm:text-base">
                        {aboutWhoWeAre.body}
                    </p>

                    <ul className="mt-8 grid gap-6 sm:grid-cols-2">
                        {aboutWhoWeAre.features.map((feature) => (
                            <li
                                key={feature.title}
                                className="border-border rounded-2xl border p-5"
                            >
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
                        className="from-foreground/85 via-foreground/25 absolute inset-0 bg-linear-to-t to-transparent"
                        aria-hidden
                    />
                    <p className="text-primary font-inter absolute inset-x-0 bottom-10 p-5 text-base font-semibold tracking-[0.10em]">
                        {aboutWhoWeAre.mediaTitle}
                    </p>
                    <p className="text-primary-foreground font-inter absolute inset-x-0 bottom-0 p-5 text-sm font-semibold sm:p-6 sm:text-lg">
                        {aboutWhoWeAre.mediaCaption}
                    </p>
                </div>
            </div>
        </section>
    );
}
