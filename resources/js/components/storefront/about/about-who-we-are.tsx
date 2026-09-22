import { useAppBrand } from '@/hooks/use-app-brand';
import { cn } from '@/lib/utils';

type AboutWhoWeAreData = {
    eyebrow: string;
    heading: string;
    body: string;
    mediaImage: string;
    mediaTitle: string;
    mediaCaption: string;
    features: { title: string; description: string }[];
};

type AboutWhoWeAreProps = {
    data: AboutWhoWeAreData;
    className?: string;
};

export function AboutWhoWeAre({ data, className }: AboutWhoWeAreProps) {
    const brand = useAppBrand();
    const body = data.body.replaceAll('{name}', brand.name);

    return (
        <section className={cn('bg-background py-16 sm:py-20', className)}>
            <div className="container mx-auto grid items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-14">
                <div>
                    <p className="text-primary text-xs font-semibold tracking-[0.14em] uppercase">
                        {data.eyebrow}
                    </p>
                    <h2 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                        {data.heading}
                    </h2>
                    <p className="text-muted-foreground font-inter mt-4 max-w-xl text-sm leading-relaxed font-normal sm:text-base">
                        {body}
                    </p>

                    <ul className="mt-8 grid gap-6 sm:grid-cols-2">
                        {data.features.map((feature) => (
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
                            backgroundImage: `url(${data.mediaImage})`,
                        }}
                        role="img"
                        aria-label={data.mediaCaption}
                    />
                    <div
                        className="from-foreground/85 via-foreground/25 absolute inset-0 bg-linear-to-t to-transparent"
                        aria-hidden
                    />
                    <p className="text-primary font-inter absolute inset-x-0 bottom-10 p-5 text-base font-semibold tracking-[0.10em]">
                        {data.mediaTitle}
                    </p>
                    <p className="text-primary-foreground font-inter absolute inset-x-0 bottom-0 p-5 text-sm font-semibold sm:p-6 sm:text-lg">
                        {data.mediaCaption}
                    </p>
                </div>
            </div>
        </section>
    );
}
