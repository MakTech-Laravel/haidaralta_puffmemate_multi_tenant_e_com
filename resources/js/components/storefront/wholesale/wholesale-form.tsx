import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Storefront/WholesaleInquiryController';
import InputError from '@/components/input-error';
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
    const { data, setData, post, processing, errors, reset } = useForm({
        full_name: '',
        phone: '',
        email: '',
        company: '',
        ask_question: '',
        address: '',
        message: '',
    });

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(WholesaleInquiryController.store.url(), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
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
                <div className="space-y-2">
                    <Label htmlFor="wholesale-full-name">
                        {copy.fullName ?? 'Full Name'} <RequiredMark />
                    </Label>
                    <Input
                        id="wholesale-full-name"
                        name="full_name"
                        
                        value={data.full_name}
                        onChange={(event) =>
                            setData('full_name', event.target.value)
                        }
                        placeholder={copy.fullNamePlaceholder}
                        className={boxedFieldClass}
                    />
                    <InputError message={errors.full_name} />
                </div>
                <div className="grid gap-5 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-phone">
                            {copy.phone ?? 'Phone'} <RequiredMark />
                        </Label>
                        <Input
                            id="wholesale-phone"
                            name="phone"
                            type="tel"
                            
                            value={data.phone}
                            onChange={(event) =>
                                setData('phone', event.target.value)
                            }
                            placeholder={copy.phonePlaceholder}
                            className={boxedFieldClass}
                        />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-email">
                            {copy.email ?? 'Email'}
                        </Label>
                        <Input
                            id="wholesale-email"
                            name="email"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            placeholder={copy.emailPlaceholder}
                            className={boxedFieldClass}
                        />
                        <InputError message={errors.email} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-company">
                            {copy.company ?? 'Company (If Applicable)'}
                        </Label>
                        <Input
                            id="wholesale-company"
                            name="company"
                            value={data.company}
                            onChange={(event) =>
                                setData('company', event.target.value)
                            }
                            placeholder={copy.companyPlaceholder}
                            className={boxedFieldClass}
                        />
                        <InputError message={errors.company} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="wholesale-ask-question">
                            {copy.askQuestion ?? 'Ask Question'}
                        </Label>
                        <Input
                            id="wholesale-ask-question"
                            name="ask_question"
                            value={data.ask_question}
                            onChange={(event) =>
                                setData('ask_question', event.target.value)
                            }
                            placeholder={copy.askQuestionPlaceholder}
                            className={boxedFieldClass}
                        />
                        <InputError message={errors.ask_question} />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="wholesale-address">
                        {copy.address ?? 'Address'} <RequiredMark />
                    </Label>
                    <Input
                        id="wholesale-address"
                        name="address"
                        
                        value={data.address}
                        onChange={(event) =>
                            setData('address', event.target.value)
                        }
                        placeholder={
                            copy.addressPlaceholder ?? 'Street, suburb, state'
                        }
                        className={boxedFieldClass}
                    />
                    <InputError message={errors.address} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="wholesale-message">
                        {copy.message ?? 'Message'} <RequiredMark />
                    </Label>
                    <Textarea
                        id="wholesale-message"
                        name="message"
                        
                        value={data.message}
                        onChange={(event) =>
                            setData('message', event.target.value)
                        }
                        placeholder={copy.messagePlaceholder}
                        rows={4}
                        className="border-border min-h-24 resize-none rounded-none bg-transparent shadow-none"
                    />
                    <InputError message={errors.message} />
                </div>

                <div className="mt-auto flex justify-end pt-4">
                    <Button
                        type="submit"
                        disabled={processing}
                        className="bg-primary text-primary-foreground hover:bg-primary/90 h-11 cursor-pointer rounded-none px-6 font-semibold"
                    >
                        {copy.submitLabel ?? 'Send Message'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
