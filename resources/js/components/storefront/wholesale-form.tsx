import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wholesaleFormCopy } from '@/lib/storefront-wholesale';
import { cn } from '@/lib/utils';

type WholesaleFormProps = {
    className?: string;
};

const boxedFieldClass =
    'border-border h-10 rounded-md bg-transparent shadow-none';

function RequiredMark() {
    return <span className="text-primary">*</span>;
}

export function WholesaleForm({ className }: WholesaleFormProps) {
    const [firstName, setFirstName] = useState('');
    const [lastName, setLastName] = useState('');
    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [company, setCompany] = useState('');
    const [askQuestion, setAskQuestion] = useState('');
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
                className="relative z-10 flex flex-1 flex-col gap-5 pb-16"
            >
                <div className="grid gap-5 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-first-name">
                            {wholesaleFormCopy.firstName} <RequiredMark />
                        </Label>
                        <Input
                            id="wholesale-first-name"
                            required
                            value={firstName}
                            onChange={(event) =>
                                setFirstName(event.target.value)
                            }
                            placeholder={
                                wholesaleFormCopy.firstNamePlaceholder
                            }
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-last-name">
                            {wholesaleFormCopy.lastName}
                        </Label>
                        <Input
                            id="wholesale-last-name"
                            value={lastName}
                            onChange={(event) =>
                                setLastName(event.target.value)
                            }
                            placeholder={
                                wholesaleFormCopy.lastNamePlaceholder
                            }
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-phone">
                            {wholesaleFormCopy.phone} <RequiredMark />
                        </Label>
                        <Input
                            id="wholesale-phone"
                            type="tel"
                            required
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            placeholder={wholesaleFormCopy.phonePlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-email">
                            {wholesaleFormCopy.email} <RequiredMark />
                        </Label>
                        <Input
                            id="wholesale-email"
                            type="email"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder={wholesaleFormCopy.emailPlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-company">
                            {wholesaleFormCopy.company}
                        </Label>
                        <Input
                            id="wholesale-company"
                            value={company}
                            onChange={(event) =>
                                setCompany(event.target.value)
                            }
                            placeholder={
                                wholesaleFormCopy.companyPlaceholder
                            }
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-ask-question">
                            {wholesaleFormCopy.askQuestion}
                        </Label>
                        <Input
                            id="wholesale-ask-question"
                            value={askQuestion}
                            onChange={(event) =>
                                setAskQuestion(event.target.value)
                            }
                            placeholder={
                                wholesaleFormCopy.askQuestionPlaceholder
                            }
                            className={boxedFieldClass}
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="wholesale-message">
                        {wholesaleFormCopy.message}
                    </Label>
                    <Textarea
                        id="wholesale-message"
                        value={message}
                        onChange={(event) => setMessage(event.target.value)}
                        placeholder={wholesaleFormCopy.messagePlaceholder}
                        rows={4}
                        className="border-border min-h-24 resize-none rounded-md bg-transparent shadow-none"
                    />
                </div>

                <div className="mt-auto flex justify-end pt-4">
                    <Button
                        type="submit"
                        className="bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-6 font-semibold"
                    >
                        {wholesaleFormCopy.submitLabel}
                    </Button>
                </div>
            </form>

            <img
                src="/images/storefront/contact-paper-plane.png"
                alt=""
                aria-hidden
                className="pointer-events-none absolute right-2 bottom-2 z-0 h-auto w-40 -rotate-6 object-contain invert mix-blend-multiply sm:right-4 sm:bottom-4 sm:w-52 sm:-rotate-3"
            />
        </div>
    );
}
