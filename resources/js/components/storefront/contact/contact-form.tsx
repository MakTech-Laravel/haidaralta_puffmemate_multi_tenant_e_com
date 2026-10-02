import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ContactInquiryController from '@/actions/App/Http/Controllers/Storefront/ContactInquiryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type ContactFormCopy = {
    fullName?: string;
    email?: string;
    phone?: string;
    type?: string;
    message?: string;
    fullNamePlaceholder?: string;
    phonePlaceholder?: string;
    typePlaceholder?: string;
    messagePlaceholder?: string;
    submitLabel?: string;
};

type ContactFormProps = {
    className?: string;
    copy: ContactFormCopy;
};

const underlineFieldClass =
    'border-border h-10 rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:border-foreground focus-visible:ring-0';

const CONTACT_TYPE_OPTIONS = [
    { value: 'general_inquiry', label: 'General inquiry' },
    { value: 'order_issue', label: 'Order issue' },
    { value: 'delivery_complaint', label: 'Delivery complaint' },
    { value: 'product_feedback', label: 'Product feedback' },
    { value: 'payment_issue', label: 'Payment issue' },
    { value: 'others', label: 'Others' },
] as const;

function RequiredMark() {
    return <span className="text-primary">*</span>;
}

export function ContactForm({ className, copy }: ContactFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        full_name: '',
        phone: '',
        email: '',
        type: '',
        message: '',
    });

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(ContactInquiryController.store.url(), {
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
                className="relative z-10 flex flex-1 flex-col gap-6"
            >
                <div className="space-y-2">
                    <Label htmlFor="contact-full-name">
                        {copy.fullName ?? 'Full Name'} <RequiredMark />
                    </Label>
                    <Input
                        id="contact-full-name"
                        name="full_name"
                        value={data.full_name}
                        onChange={(event) =>
                            setData('full_name', event.target.value)
                        }
                        placeholder={copy.fullNamePlaceholder}
                        className={underlineFieldClass}
                    />
                    <InputError message={errors.full_name} />
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="contact-phone">
                            {copy.phone ?? 'Phone Number'} <RequiredMark />
                        </Label>
                        <Input
                            id="contact-phone"
                            name="phone"
                            type="tel"
                            value={data.phone}
                            onChange={(event) =>
                                setData('phone', event.target.value)
                            }
                            placeholder={copy.phonePlaceholder}
                            className={underlineFieldClass}
                        />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="contact-email">
                            {copy.email ?? 'Email'}
                        </Label>
                        <Input
                            id="contact-email"
                            name="email"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            className={underlineFieldClass}
                        />
                        <InputError message={errors.email} />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="contact-type">
                        {copy.type ?? 'Type'} <RequiredMark />
                    </Label>
                    <Select
                        value={data.type || undefined}
                        onValueChange={(value) => setData('type', value)}
                    >
                        <SelectTrigger
                            id="contact-type"
                            className={cn(
                                underlineFieldClass,
                                'w-full justify-between',
                            )}
                        >
                            <SelectValue
                                placeholder={
                                    copy.typePlaceholder ??
                                    'Select a contact type'
                                }
                            />
                        </SelectTrigger>
                        <SelectContent>
                            {CONTACT_TYPE_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="contact-message">
                        {copy.message ?? 'Message'} <RequiredMark />
                    </Label>
                    <Textarea
                        id="contact-message"
                        name="message"
                        value={data.message}
                        onChange={(event) =>
                            setData('message', event.target.value)
                        }
                        placeholder={copy.messagePlaceholder}
                        rows={3}
                        className="border-border focus-visible:border-foreground min-h-20 resize-none rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:ring-0"
                    />
                    <InputError message={errors.message} />
                </div>

                <div className="mt-auto flex justify-end pt-8 pb-10 sm:pb-20">
                    <Button
                        type="submit"
                        disabled={processing}
                        className="bg-foreground text-background hover:bg-foreground/90 h-11 rounded-md px-6 font-semibold"
                    >
                        {copy.submitLabel ?? 'Send Message'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
