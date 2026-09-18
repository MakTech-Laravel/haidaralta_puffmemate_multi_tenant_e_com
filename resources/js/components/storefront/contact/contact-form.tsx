import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { contactFormCopy } from '@/lib/storefront-contact';
import { cn } from '@/lib/utils';

type ContactFormProps = {
    className?: string;
};

const underlineFieldClass =
    'border-border h-10 rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:border-foreground focus-visible:ring-0';

export function ContactForm({ className }: ContactFormProps) {
    const [firstName, setFirstName] = useState('');
    const [lastName, setLastName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [message, setMessage] = useState('');

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
    };

    return (
        <div
            className={cn(
                'bg-background relative flex h-full min-h-full flex-col p-6 sm:p-8 lg:p-10',
                className,
            )}
        >
            <form
                onSubmit={onSubmit}
                className="relative z-10 flex flex-1 flex-col gap-6"
            >
                <div className="grid gap-6 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="contact-first-name">
                            {contactFormCopy.firstName}
                        </Label>
                        <Input
                            id="contact-first-name"
                            value={firstName}
                            onChange={(event) =>
                                setFirstName(event.target.value)
                            }
                            className={underlineFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-last-name">
                            {contactFormCopy.lastName}
                        </Label>
                        <Input
                            id="contact-last-name"
                            value={lastName}
                            onChange={(event) =>
                                setLastName(event.target.value)
                            }
                            placeholder={contactFormCopy.lastNamePlaceholder}
                            className={underlineFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-email">
                            {contactFormCopy.email}
                        </Label>
                        <Input
                            id="contact-email"
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className={underlineFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-phone">
                            {contactFormCopy.phone}
                        </Label>
                        <Input
                            id="contact-phone"
                            type="tel"
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            placeholder={contactFormCopy.phonePlaceholder}
                            className={underlineFieldClass}
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="contact-message">
                        {contactFormCopy.message}
                    </Label>
                    <Textarea
                        id="contact-message"
                        value={message}
                        onChange={(event) => setMessage(event.target.value)}
                        placeholder={contactFormCopy.messagePlaceholder}
                        rows={3}
                        className="border-border focus-visible:border-foreground min-h-20 resize-none rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:ring-0"
                    />
                </div>

                <div className="mt-auto flex justify-end pt-8 pb-10 sm:pb-20">
                    <Button
                        type="submit"
                        className="bg-foreground text-background hover:bg-foreground/90 h-11 rounded-md px-6 font-semibold"
                    >
                        {contactFormCopy.submitLabel}
                    </Button>
                </div>
            </form>

            {/* <img
                src="/images/storefront/contact-paper-plane.png"
                alt=""
                aria-hidden
                className="pb-16 pointer-events-none absolute right-2 bottom-2 z-0 h-auto w-40 -rotate-6 object-contain invert mix-blend-multiply sm:right-4 sm:bottom-4 sm:w-52 sm:-rotate-40"
            /> */}
        </div>
    );
}
