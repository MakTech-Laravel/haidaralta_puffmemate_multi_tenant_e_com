import { Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Storefront/WholesaleInquiryController';
import InputError from '@/components/input-error';
import { CheckoutMapDialog } from '@/components/storefront/checkout/checkout-map-dialog';
import { AddressSuggestInput } from '@/components/storefront/wholesale/address-suggest-input';
import { WholesaleProductPicker } from '@/components/storefront/wholesale/wholesale-product-picker';
import type { WholesalePickerLine } from '@/components/storefront/wholesale/wholesale-product-picker';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wholesaleFormCopy } from '@/lib/storefront-wholesale';
import { cn } from '@/lib/utils';
import { index as wholesaleRequestsIndex } from '@/routes/customer/wholesale-requests';
import type { Auth } from '@/types/auth';

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

export function WholesaleForm({
    className,
    copy: cmsCopy,
}: WholesaleFormProps) {
    // Saved CMS payloads are not merged with defaults; keys added later fall back here.
    const copy: WholesaleFormCopy = { ...wholesaleFormCopy, ...cmsCopy };
    const { auth } = usePage<{ auth: Auth }>().props;
    const customer = auth.user?.user_type === 'customer' ? auth.user : null;
    const [lines, setLines] = useState<WholesalePickerLine[]>([]);
    const { data, setData, post, processing, errors, reset, transform } =
        useForm({
            full_name: customer?.name ?? '',
            phone: customer?.phone ?? '',
            email: customer?.email ?? '',
            company: '',
            address: '',
            latitude: '',
            longitude: '',
            message: '',
        });
    const fieldErrors = errors as Record<string, string | undefined>;

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        transform((current) => ({
            ...current,
            items: lines.map((line) => ({
                product_id: line.productId,
                variant_id: line.variantId,
                quantity: line.quantity,
            })),
        }));
        post(WholesaleInquiryController.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setLines([]);
            },
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
                    <div className="space-y-2 sm:col-span-2">
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
                </div>

                <div className="space-y-2">
                    <div className="flex items-end gap-2">
                        <div className="min-w-0 flex-1 space-y-2">
                            <Label htmlFor="wholesale-address">
                                {copy.address ?? 'Address'} <RequiredMark />
                            </Label>
                            <AddressSuggestInput
                                id="wholesale-address"
                                name="address"
                                value={data.address}
                                placeholder={
                                    copy.addressPlaceholder ??
                                    'Start typing and select an address'
                                }
                                className={boxedFieldClass}
                                onSelect={({
                                    address,
                                    latitude,
                                    longitude,
                                }) => {
                                    setData((current) => ({
                                        ...current,
                                        address,
                                        latitude,
                                        longitude,
                                    }));
                                }}
                                onClear={() => {
                                    setData((current) => ({
                                        ...current,
                                        address: '',
                                        latitude: '',
                                        longitude: '',
                                    }));
                                }}
                            />
                        </div>
                        <CheckoutMapDialog
                            latitude={data.latitude}
                            longitude={data.longitude}
                            onSelect={({ latitude, longitude, address }) => {
                                setData((current) => ({
                                    ...current,
                                    latitude,
                                    longitude,
                                    address: address || current.address,
                                }));
                            }}
                        />
                    </div>
                    <InputError message={errors.address} />
                    <InputError message={errors.latitude} />
                    <InputError message={errors.longitude} />
                    {!data.latitude || !data.longitude ? (
                        <Alert variant="destructive">
                            <AlertDescription>
                                Select an address from the suggestions or pin
                                your location on the map so we can follow up on
                                your wholesale inquiry.
                            </AlertDescription>
                        </Alert>
                    ) : null}
                </div>

                <div className="space-y-2">
                    <Label>{copy.products ?? 'Products'}</Label>
                    <p className="text-muted-foreground text-xs">
                        {copy.productsHelp ??
                            'Add the products and quantities you need. Final pricing is confirmed by our team.'}
                    </p>
                    <WholesaleProductPicker
                        optionsUrl={WholesaleInquiryController.products.url()}
                        lines={lines}
                        onChange={setLines}
                        errors={fieldErrors}
                        searchPlaceholder={
                            copy.productsPlaceholder ?? 'Search products'
                        }
                        triggerClassName={boxedFieldClass}
                        disabled={processing}
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="wholesale-message">
                        {copy.message ?? 'Message'}{' '}
                        {lines.length === 0 ? <RequiredMark /> : null}
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

                <div className="mt-auto flex flex-wrap items-center justify-end gap-4 pt-4">
                    {customer ? (
                        <Link
                            href={wholesaleRequestsIndex.url()}
                            prefetch
                            className="text-primary text-sm font-medium underline-offset-4 hover:underline"
                        >
                            {copy.myRequests ?? 'My wholesale requests'}
                        </Link>
                    ) : null}
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
