import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type WholesaleFormCopy = Record<string, string>;

type WholesaleFormProps = {
    className?: string;
    copy: WholesaleFormCopy;
};

const boxedFieldClass =
    'border-border h-10  rounded-none bg-transparent shadow-none';

function RequiredMark() {
    return <span className="text-primary">*</span>;
}

export function WholesaleForm({ className, copy }: WholesaleFormProps) {
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
                            {copy.firstName ?? 'First Name'} <RequiredMark />
                        </Label>
                        <Input
                            id="wholesale-first-name"
                            required
                            value={firstName}
                            onChange={(event) =>
                                setFirstName(event.target.value)
                            }
                            placeholder={copy.firstNamePlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-last-name">
                            {copy.lastName ?? 'Last Name'}
                        </Label>
                        <Input
                            id="wholesale-last-name"
                            value={lastName}
                            onChange={(event) =>
                                setLastName(event.target.value)
                            }
                            placeholder={copy.lastNamePlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-phone">
                            {copy.phone ?? 'Phone'} <RequiredMark />
                        </Label>
                        <Input
                            id="wholesale-phone"
                            type="tel"
                            required
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            placeholder={copy.phonePlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-email">
                            {copy.email ?? 'Email'} <RequiredMark />
                        </Label>
                        <Input
                            id="wholesale-email"
                            type="email"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder={copy.emailPlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-company">
                            {copy.company ?? 'Company (If Applicable)'}
                        </Label>
                        <Input
                            id="wholesale-company"
                            value={company}
                            onChange={(event) => setCompany(event.target.value)}
                            placeholder={copy.companyPlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-ask-question">
                            {copy.askQuestion ?? 'Ask Question'}
                        </Label>
                        <Input
                            id="wholesale-ask-question"
                            value={askQuestion}
                            onChange={(event) =>
                                setAskQuestion(event.target.value)
                            }
                            placeholder={copy.askQuestionPlaceholder}
                            className={boxedFieldClass}
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="wholesale-message">
                        {copy.message ?? 'Message'}
                    </Label>
                    <Textarea
                        id="wholesale-message"
                        value={message}
                        onChange={(event) => setMessage(event.target.value)}
                        placeholder={copy.messagePlaceholder}
                        rows={4}
                        className="border-border min-h-24 resize-none rounded-none bg-transparent shadow-none"
                    />
                </div>

                <div className="mt-auto flex justify-end pt-4">
                    <Button
                        type="submit"
                        className="bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-6 font-semibold"
                    >
                        {copy.submitLabel ?? 'Send Message'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
