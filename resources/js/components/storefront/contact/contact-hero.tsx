import { AtSign, Mail, MessageCircle, Phone } from 'lucide-react';
import { contactHero } from '@/lib/storefront-contact';
import { cn } from '@/lib/utils';

type ContactHeroProps = {
    className?: string;
};

const heroIcons = [
    { id: 'chat', Icon: MessageCircle },
    { id: 'phone', Icon: Phone },
    { id: 'at', Icon: AtSign },
    { id: 'mail', Icon: Mail },
] as const;

export function ContactHero({ className }: ContactHeroProps) {
    return (
        <section
            className={cn(
                'relative isolate min-h-[36vh] overflow-hidden sm:min-h-[42vh]',
                className,
            )}
        >
            <div
                className="absolute inset-0 bg-foreground bg-cover bg-center"
                style={{ backgroundImage: `url(${contactHero.image})` }}
                aria-hidden
            />
            <div
                className="absolute inset-0 bg-linear-to-r from-foreground/85 via-foreground/60 to-foreground/40"
                aria-hidden
            />

            <div className="relative mx-auto flex min-h-[36vh] container items-center justify-between gap-8 px-4 py-14 sm:min-h-[42vh] sm:px-6 sm:py-16">
                <div className="text-primary-foreground max-w-xl">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        {contactHero.title}
                    </h1>
                    <p className="mt-4 max-w-md text-sm leading-relaxed opacity-90 sm:text-base font-normal font-inter">
                        {contactHero.subtitle}
                    </p>
                </div>

                <div
                    className="border-primary-foreground/35 text-primary-foreground hidden items-center gap-4 rounded-full border px-5 py-3 md:flex"
                    aria-hidden
                >
                    {heroIcons.map(({ id, Icon }) => (
                        <span
                            key={id}
                            className="flex size-9 items-center justify-center"
                        >
                            <Icon className="size-5" strokeWidth={1.5} />
                        </span>
                    ))}
                </div>
            </div>
        </section>
    );
}
