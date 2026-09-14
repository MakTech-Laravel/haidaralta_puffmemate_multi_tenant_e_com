import { aboutHero } from '@/lib/storefront-about';
import { cn } from '@/lib/utils';

type AboutHeroProps = {
    className?: string;
};

export function AboutHero({ className }: AboutHeroProps) {
    return (
        <section
            className={cn(
                'relative isolate min-h-[40vh] overflow-hidden sm:min-h-[48vh]',
                className,
            )}
        >
            <div
                className="absolute inset-0 bg-foreground bg-cover bg-center"
                style={{ backgroundImage: `url(${aboutHero.image})` }}
                aria-hidden
            />
            <div
                className="absolute inset-0 bg-linear-to-r from-foreground/80 via-foreground/55 to-foreground/35"
                aria-hidden
            />

            <div className="relative mx-auto flex min-h-[40vh] container flex-col justify-center px-4 py-16 sm:min-h-[48vh] sm:px-6 sm:py-20">
                <div className="text-primary-foreground max-w-xl">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        {aboutHero.title}
                    </h1>
                    <p className="mt-4 max-w-md text-sm leading-relaxed opacity-90 sm:text-base font-inter font-normal">
                        {aboutHero.subtitle}
                    </p>
                </div>
            </div>
        </section>
    );
}
