import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type ContactFormCopy = {
    firstName?: string;
    lastName?: string;
    email?: string;
    phone?: string;
    message?: string;
    lastNamePlaceholder?: string;
    phonePlaceholder?: string;
    messagePlaceholder?: string;
    submitLabel?: string;
};

type ContactFormProps = {
    className?: string;
    copy: ContactFormCopy;
};

const underlineFieldClass =
    'border-border h-10 rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:border-foreground focus-visible:ring-0';

export function ContactForm({ className, copy }: ContactFormProps) {
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
                            {copy.firstName ?? 'First Name'}
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
                            {copy.lastName ?? 'Last Name'}
                        </Label>
                        <Input
                            id="contact-last-name"
                            value={lastName}
                            onChange={(event) =>
                                setLastName(event.target.value)
                            }
                            placeholder={copy.lastNamePlaceholder}
                            className={underlineFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-email">
                            {copy.email ?? 'Email'}
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
                            {copy.phone ?? 'Phone Number'}
                        </Label>
                        <Input
                            id="contact-phone"
                            type="tel"
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            placeholder={copy.phonePlaceholder}
                            className={underlineFieldClass}
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="contact-message">
                        {copy.message ?? 'Message'}
                    </Label>
                    <Textarea
                        id="contact-message"
                        value={message}
                        onChange={(event) => setMessage(event.target.value)}
                        placeholder={copy.messagePlaceholder}
                        rows={3}
                        className="border-border focus-visible:border-foreground min-h-20 resize-none rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:ring-0"
                    />
                </div>

                <div className="mt-auto flex justify-end pt-8 pb-10 sm:pb-20">
                    <Button
                        type="submit"
                        className="bg-foreground text-background hover:bg-foreground/90 h-11 rounded-md px-6 font-semibold"
                    >
                        {copy.submitLabel ?? 'Send Message'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
