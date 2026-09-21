import { Head, useForm } from '@inertiajs/react';
import PaymentGatewaySettingsController from '@/actions/App/Http/Controllers/Platform/PaymentGatewaySettingsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type Method = {
    key: string;
    label: string;
    description: string | null;
    enabled: boolean;
};

type Props = {
    methods: Method[];
};

export default function PlatformPaymentGatewaySettings({ methods }: Props) {
    const form = useForm({
        methods: methods.map((method) => ({
            key: method.key,
            label: method.label,
            description: method.description ?? '',
            enabled: method.enabled,
        })),
    });

    return (
        <>
            <Head title="Payment Gateway" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Payment Gateway"
                    description="Enable checkout payment methods and customize their labels. At least one method must stay enabled."
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(
                            PaymentGatewaySettingsController.update.url(),
                            {
                                preserveScroll: true,
                            },
                        );
                    }}
                >
                    {form.data.methods.map((method, index) => (
                        <section key={method.key} className={settingsCardClass}>
                            <div className="mb-5 flex items-start justify-between gap-4">
                                <div className="space-y-1">
                                    <h2 className="text-base font-semibold tracking-tight capitalize">
                                        {method.key.replace('_', ' ')}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        Stable key: {method.key}
                                    </p>
                                </div>
                                <Switch
                                    checked={method.enabled}
                                    onCheckedChange={(checked) => {
                                        const next = form.data.methods.map(
                                            (row, rowIndex) =>
                                                rowIndex === index
                                                    ? {
                                                          ...row,
                                                          enabled: checked,
                                                      }
                                                    : row,
                                        );
                                        form.setData('methods', next);
                                    }}
                                />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor={`label-${method.key}`}>
                                        Label
                                    </Label>
                                    <Input
                                        id={`label-${method.key}`}
                                        value={method.label}
                                        onChange={(event) => {
                                            const next =
                                                form.data.methods.map(
                                                    (row, rowIndex) =>
                                                        rowIndex === index
                                                            ? {
                                                                  ...row,
                                                                  label: event
                                                                      .target
                                                                      .value,
                                                              }
                                                            : row,
                                                );
                                            form.setData('methods', next);
                                        }}
                                    />
                                    <InputError
                                        message={
                                            form.errors[
                                                `methods.${index}.label`
                                            ]
                                        }
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label
                                        htmlFor={`description-${method.key}`}
                                    >
                                        Description
                                    </Label>
                                    <Input
                                        id={`description-${method.key}`}
                                        value={method.description}
                                        onChange={(event) => {
                                            const next =
                                                form.data.methods.map(
                                                    (row, rowIndex) =>
                                                        rowIndex === index
                                                            ? {
                                                                  ...row,
                                                                  description:
                                                                      event
                                                                          .target
                                                                          .value,
                                                              }
                                                            : row,
                                                );
                                            form.setData('methods', next);
                                        }}
                                    />
                                    <InputError
                                        message={
                                            form.errors[
                                                `methods.${index}.description`
                                            ]
                                        }
                                    />
                                </div>
                            </div>
                        </section>
                    ))}

                    <InputError message={form.errors.methods} />

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            Save payment gateways
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PlatformPaymentGatewaySettings.layout = {
    breadcrumbs: [
        {
            title: 'Payment Gateway',
            href: '/platform/settings/payment-gateway',
        },
    ],
};
