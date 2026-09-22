import { Mail, MapPin, Phone } from 'lucide-react';
import { ContactSocialLinks } from '@/components/storefront/contact-social-links';
import { useContacts } from '@/hooks/use-contacts';
import { cn } from '@/lib/utils';

type ContactInfoProps = {
    className?: string;
    title?: string;
    subtitle?: string;
};

export function ContactInfo({
    className,
    title = 'Contact Information',
    subtitle = 'Say something to start a live chat!',
}: ContactInfoProps) {
    const contacts = useContacts();

    return (
        <aside
            className={cn(
                'bg-foreground text-primary-foreground relative flex min-h-80 flex-col overflow-hidden p-6 sm:p-8',
                className,
            )}
        >
            <div className="relative z-10">
                <h2 className="font-inter text-xl font-semibold tracking-tight sm:text-[28px]">
                    {title}
                </h2>
                <p className="font-inter mt-2 text-lg font-normal text-white/70">
                    {subtitle}
                </p>

                <ul className="font-inter mt-10 space-y-6 text-sm font-normal sm:text-base">
                    {contacts.phone ? (
                        <li className="flex items-start gap-3">
                            <Phone
                                className="mt-0.5 size-4 shrink-0"
                                aria-hidden
                            />
                            <a
                                href={`tel:${contacts.phone.replace(/\s+/g, '')}`}
                                className="hover:underline"
                            >
                                {contacts.phone}
                            </a>
                        </li>
                    ) : null}
                    {contacts.email ? (
                        <li className="flex items-start gap-3">
                            <Mail
                                className="mt-0.5 size-4 shrink-0"
                                aria-hidden
                            />
                            <a
                                href={`mailto:${contacts.email}`}
                                className="hover:underline"
                            >
                                {contacts.email}
                            </a>
                        </li>
                    ) : null}
                    {contacts.address ? (
                        <li className="flex items-start gap-3">
                            <MapPin
                                className="mt-0.5 size-4 shrink-0"
                                aria-hidden
                            />
                            <span className="leading-relaxed">
                                {contacts.address}
                            </span>
                        </li>
                    ) : null}
                </ul>
            </div>

            <ContactSocialLinks socials={contacts.socials} />

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
