import { Mail, MapPin, Phone } from 'lucide-react';
import { contactInfo } from '@/lib/storefront-contact';
import { cn } from '@/lib/utils';

type ContactInfoProps = {
    className?: string;
};

function SocialGlyph({ id }: { id: (typeof contactInfo.socials)[number]['id'] }) {
    if (id === 'twitter') {
        return (
            <svg viewBox="0 0 24 24" className="size-3.5 fill-current" aria-hidden>
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.727-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z" />
            </svg>
        );
    }

    if (id === 'instagram') {
        return (
            <svg viewBox="0 0 24 24" className="size-3.5 fill-current" aria-hidden>
                <path d="M12 7.2A4.8 4.8 0 1 0 12 16.8 4.8 4.8 0 0 0 12 7.2zm0 7.92A3.12 3.12 0 1 1 12 8.88a3.12 3.12 0 0 1 0 6.24zm6.24-7.98a1.12 1.12 0 1 1-2.24 0 1.12 1.12 0 0 1 2.24 0zM12 3.6c-2.28 0-2.57.01-3.47.05-.89.04-1.5.18-2.03.39a4.1 4.1 0 0 0-1.48.96 4.1 4.1 0 0 0-.96 1.48c-.21.53-.35 1.14-.39 2.03-.04.9-.05 1.19-.05 3.47s.01 2.57.05 3.47c.04.89.18 1.5.39 2.03.22.54.5 1 .96 1.48.48.46.94.74 1.48.96.53.21 1.14.35 2.03.39.9.04 1.19.05 3.47.05s2.57-.01 3.47-.05c.89-.04 1.5-.18 2.03-.39a4.1 4.1 0 0 0 1.48-.96 4.1 4.1 0 0 0 .96-1.48c.21-.53.35-1.14.39-2.03.04-.9.05-1.19.05-3.47s-.01-2.57-.05-3.47c-.04-.89-.18-1.5-.39-2.03a4.1 4.1 0 0 0-.96-1.48 4.1 4.1 0 0 0-1.48-.96c-.53-.21-1.14-.35-2.03-.39-.9-.04-1.19-.05-3.47-.05zm0 1.52c2.24 0 2.5.01 3.38.05.82.04 1.26.17 1.56.29.39.15.67.33.96.62.29.29.47.57.62.96.12.3.25.74.29 1.56.04.88.05 1.14.05 3.38s-.01 2.5-.05 3.38c-.04.82-.17 1.26-.29 1.56-.15.39-.33.67-.62.96-.29.29-.57.47-.96.62-.3.12-.74.25-1.56.29-.88.04-1.14.05-3.38.05s-2.5-.01-3.38-.05c-.82-.04-1.26-.17-1.56-.29a2.58 2.58 0 0 1-.96-.62 2.58 2.58 0 0 1-.62-.96c-.12-.3-.25-.74-.29-1.56-.04-.88-.05-1.14-.05-3.38s.01-2.5.05-3.38c.04-.82.17-1.26.29-1.56.15-.39.33-.67.62-.96.29-.29.57-.47.96-.62.3-.12.74-.25 1.56-.29.88-.04 1.14-.05 3.38-.05z" />
            </svg>
        );
    }

    return (
        <svg viewBox="0 0 24 24" className="size-3.5 fill-current" aria-hidden>
            <path d="M19.54 5.21a9.78 9.78 0 0 0-3.58-1.41 11.3 11.3 0 0 0-.52 1.07 9.07 9.07 0 0 0-6.88 0c-.17-.35-.35-.72-.52-1.07a9.78 9.78 0 0 0-3.58 1.41A11.63 11.63 0 0 0 1.4 16.5a10.2 10.2 0 0 0 3.16 1.62c.26-.35.49-.72.7-1.1a6.73 6.73 0 0 1-1.1-.53c.09-.07.18-.13.26-.2a7.63 7.63 0 0 0 6.5 3.07 7.63 7.63 0 0 0 6.5-3.07c.09.07.18.14.26.2-.35.2-.72.39-1.1.53.21.38.44.75.7 1.1a10.2 10.2 0 0 0 3.16-1.62 11.63 11.63 0 0 0-2.5-11.29zM8.68 14.68a1.52 1.52 0 1 1 0-3.04 1.52 1.52 0 0 1 0 3.04zm6.64 0a1.52 1.52 0 1 1 0-3.04 1.52 1.52 0 0 1 0 3.04z" />
        </svg>
    );
}

export function ContactInfo({ className }: ContactInfoProps) {
    return (
        <aside
            className={cn(
                'bg-foreground text-primary-foreground relative flex min-h-80 flex-col overflow-hidden p-6 sm:p-8',
                className,
            )}
        >
            <div className="relative z-10">
                <h2 className="text-xl font-semibold tracking-tight sm:text-[28px] font-inter">
                    {contactInfo.title}
                </h2>
                <p className="mt-2  text-white/70 font-inter font-normal text-lg">
                    {contactInfo.subtitle}
                </p>

                <ul className="mt-10 space-y-6 text-sm sm:text-base font-inter font-normal">
                    <li className="flex items-start gap-3">
                        <Phone className="mt-0.5 size-4 shrink-0" aria-hidden />
                        <a
                            href={`tel:${contactInfo.phone.replace(/\s+/g, '')}`}
                            className="hover:underline"
                        >
                            {contactInfo.phone}
                        </a>
                    </li>
                    <li className="flex items-start gap-3">
                        <Mail className="mt-0.5 size-4 shrink-0" aria-hidden />
                        <a
                            href={`mailto:${contactInfo.email}`}
                            className="hover:underline"
                        >
                            {contactInfo.email}
                        </a>
                    </li>
                    <li className="flex items-start gap-3">
                        <MapPin className="mt-0.5 size-4 shrink-0" aria-hidden />
                        <span className="leading-relaxed">{contactInfo.address}</span>
                    </li>
                </ul>
            </div>

            <div className="relative z-10 mt-auto flex gap-3 pt-12">
                {contactInfo.socials.map((social) => (
                    <a
                        key={social.id}
                        href={social.href}
                        aria-label={social.label}
                        className="bg-primary text-primary-foreground hover:bg-primary/90 flex size-9 items-center justify-center rounded-full transition"
                    >
                        <SocialGlyph id={social.id} />
                    </a>
                ))}
            </div>

            <div
                className="pointer-events-none absolute -right-16 -bottom-20 size-56 rounded-full bg-white/10"
                aria-hidden
            />
            <div
                className="pointer-events-none absolute -right-6 -bottom-6 size-36 rounded-full bg-white/10"
                aria-hidden
            />
        </aside>
    );
}
