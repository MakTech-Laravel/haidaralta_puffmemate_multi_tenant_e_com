import { cn } from '@/lib/utils';

type AboutHeroData = {
    title: string;
    subtitle: string;
    image: string;
};

type AboutHeroProps = {
    data: AboutHeroData;
    className?: string;
};

export function AboutHero({ data, className }: AboutHeroProps) {
    return (
        <section
            className={cn(
                'relative isolate min-h-[40vh] overflow-hidden sm:min-h-[48vh]',
                className,
            )}
        >
            <div
                className="bg-foreground absolute inset-0 bg-cover bg-center"
                style={{ backgroundImage: `url(${data.image})` }}
                aria-hidden
            />
            <div
                className="from-foreground/80 via-foreground/55 to-foreground/35 absolute inset-0 bg-linear-to-r"
                aria-hidden
            />

            <div className="relative container mx-auto flex min-h-[40vh] flex-col justify-center px-4 py-16 sm:min-h-[48vh] sm:px-6 sm:py-20">
                <div className="text-primary-foreground max-w-xl">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        {data.title}
                    </h1>
                    <p className="font-inter mt-4 max-w-md text-sm leading-relaxed font-normal opacity-90 sm:text-base">
                        {data.subtitle}
                    </p>
                </div>
            </div>
        </section>
    );
}
