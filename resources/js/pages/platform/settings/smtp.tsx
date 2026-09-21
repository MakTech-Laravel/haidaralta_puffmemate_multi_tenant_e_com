import { Head, useForm } from '@inertiajs/react';
import SmtpSettingsController from '@/actions/App/Http/Controllers/Platform/SmtpSettingsController';
import Heading from '@/components/heading';
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
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type Settings = {
    enabled: boolean;
    host: string | null;
    port: number | null;
    encryption: string | null;
    username: string | null;
    has_password: boolean;
    from_address: string | null;
    from_name: string | null;
    reply_to_address: string | null;
};

type Props = {
    settings: Settings;
    defaultTestEmail: string | null;
};

export default function PlatformSmtpSettings({
    settings,
    defaultTestEmail,
}: Props) {
    const form = useForm({
        enabled: settings.enabled,
        host: settings.host ?? '',
        port: settings.port ?? 587,
        encryption: settings.encryption ?? 'tls',
        username: settings.username ?? '',
        password: '',
        from_address: settings.from_address ?? '',
        from_name: settings.from_name ?? '',
        reply_to_address: settings.reply_to_address ?? '',
    });

    const testForm = useForm({
        email: defaultTestEmail ?? '',
    });

    return (
        <>
            <Head title="SMTP settings" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="SMTP"
                    description="Configure outbound email. Leave password blank to keep the current value."
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(SmtpSettingsController.update.url(), {
                            preserveScroll: true,
                        });
                    }}
                >
                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Connection
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                When custom SMTP is off, the app keeps the
                                mailer from deployment config.
                            </p>
                        </div>

                        <div className="flex flex-col gap-5">
                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div>
                                    <Label htmlFor="smtp_enabled">
                                        Use custom SMTP
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Override the default mailer with these
                                        credentials.
                                    </p>
                                </div>
                                <Switch
                                    id="smtp_enabled"
                                    checked={form.data.enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData('enabled', checked)
                                    }
                                />
                            </div>
                            <InputError message={form.errors.enabled} />

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="host">Host</Label>
                                    <Input
                                        id="host"
                                        value={form.data.host}
                                        onChange={(event) =>
                                            form.setData(
                                                'host',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.host} />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="port">Port</Label>
                                    <Input
                                        id="port"
                                        type="number"
                                        value={form.data.port}
                                        onChange={(event) =>
                                            form.setData(
                                                'port',
                                                Number(event.target.value),
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.port} />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label>Encryption</Label>
                                    <Select
                                        value={form.data.encryption}
                                        onValueChange={(value) =>
                                            form.setData('encryption', value)
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="tls">
                                                TLS
                                            </SelectItem>
                                            <SelectItem value="ssl">
                                                SSL
                                            </SelectItem>
                                            <SelectItem value="none">
                                                None
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.encryption}
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="username">Username</Label>
                                    <Input
                                        id="username"
                                        value={form.data.username}
                                        onChange={(event) =>
                                            form.setData(
                                                'username',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.username}
                                    />
                                </div>
                                <div className="flex flex-col gap-2 sm:col-span-2">
                                    <Label htmlFor="password">
                                        Password
                                        {settings.has_password
                                            ? ' (saved — leave blank to keep)'
                                            : ''}
                                    </Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        autoComplete="new-password"
                                        value={form.data.password}
                                        onChange={(event) =>
                                            form.setData(
                                                'password',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.password}
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Sender identity
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                From and reply-to addresses used on outbound
                                mail.
                            </p>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="from_address">
                                    From address
                                </Label>
                                <Input
                                    id="from_address"
                                    type="email"
                                    value={form.data.from_address}
                                    onChange={(event) =>
                                        form.setData(
                                            'from_address',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.from_address}
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="from_name">From name</Label>
                                <Input
                                    id="from_name"
                                    value={form.data.from_name}
                                    onChange={(event) =>
                                        form.setData(
                                            'from_name',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.from_name} />
                            </div>
                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="reply_to_address">
                                    Reply-to address
                                </Label>
                                <Input
                                    id="reply_to_address"
                                    type="email"
                                    value={form.data.reply_to_address}
                                    onChange={(event) =>
                                        form.setData(
                                            'reply_to_address',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.reply_to_address}
                                />
                            </div>
                        </div>
                    </section>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            Save SMTP settings
                        </Button>
                    </div>
                </form>

                <section className={settingsCardClass}>
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Send test email
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Uses the saved SMTP configuration.
                        </p>
                    </div>
                    <form
                        className="flex flex-col gap-3 sm:flex-row sm:items-end"
                        onSubmit={(event) => {
                            event.preventDefault();
                            testForm.post(SmtpSettingsController.test.url(), {
                                preserveScroll: true,
                            });
                        }}
                    >
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="test_email">Recipient</Label>
                            <Input
                                id="test_email"
                                type="email"
                                value={testForm.data.email}
                                onChange={(event) =>
                                    testForm.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={testForm.errors.email} />
                        </div>
                        <Button
                            type="submit"
                            variant="secondary"
                            disabled={testForm.processing}
                        >
                            Send test
                        </Button>
                    </form>
                </section>
            </div>
        </>
    );
}

PlatformSmtpSettings.layout = {
    breadcrumbs: [
        {
            title: 'SMTP',
            href: '/platform/settings/smtp',
        },
    ],
};
