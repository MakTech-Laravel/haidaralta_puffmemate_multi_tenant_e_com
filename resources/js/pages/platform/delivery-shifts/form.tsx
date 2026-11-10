import { Form, Head, Link } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import InputError from '@/components/input-error';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { TimePicker } from '@/components/ui/time-picker';
import { durationLabel, formatWindow } from '@/lib/shift-format';
import { cn } from '@/lib/utils';

type DeliveryShiftForm = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    is_active: boolean;
};

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <section className={settingsCardClass}>
            <div className="mb-5 space-y-1">
                <h2 className="text-foreground text-base font-semibold tracking-tight">
                    {title}
                </h2>
                {description ? (
                    <p className="text-muted-foreground text-sm leading-relaxed">
                        {description}
                    </p>
                ) : null}
            </div>
            <div className="flex flex-col gap-5">{children}</div>
        </section>
    );
}

export default function PlatformDeliveryShiftForm({
    shift,
}: {
    shift: DeliveryShiftForm | null;
}) {
    const isEdit = shift !== null;
    const [startTime, setStartTime] = useState(shift?.start_time ?? '');
    const [endTime, setEndTime] = useState(shift?.end_time ?? '');
    const [isActive, setIsActive] = useState(shift?.is_active ?? true);

    return (
        <>
            <Head
                title={isEdit ? 'Edit delivery shift' : 'Add delivery shift'}
            />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={isEdit ? 'Edit delivery shift' : 'Add delivery shift'}
                    description="Platform-only named window. End time must be after start on the same day."
                />

                <Form
                    action={
                        isEdit
                            ? `/platform/delivery-shifts/${shift.id}`
                            : '/platform/delivery-shifts'
                    }
                    method={isEdit ? 'put' : 'post'}
                    className="space-y-6"
                    options={{ preserveScroll: true }}
                    validationTimeout={500}
                >
                    {({ processing, errors, validate }) => {
                        const invalidWindow =
                            Boolean(startTime && endTime) &&
                            durationLabel(startTime, endTime) === '';

                        return (
                            <>
                                <Section title="Shift">
                                    <div className="flex items-center gap-3 rounded-xl border p-4">
                                        <Checkbox
                                            id="is_active"
                                            checked={isActive}
                                            onCheckedChange={(checked) =>
                                                setIsActive(checked === true)
                                            }
                                        />
                                        <div>
                                            <Label htmlFor="is_active">
                                                Active
                                            </Label>
                                            <p className="text-muted-foreground text-sm">
                                                Inactive shifts stay listed but
                                                are marked off.
                                            </p>
                                        </div>
                                        <input
                                            type="hidden"
                                            name="is_active"
                                            value={isActive ? '1' : '0'}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={shift?.name ?? ''}
                                            required
                                            placeholder="Morning"
                                            autoComplete="off"
                                            aria-invalid={Boolean(errors.name)}
                                            onBlur={() => validate('name')}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>Start</Label>
                                            <TimePicker
                                                id="start_time"
                                                value={startTime}
                                                onChange={setStartTime}
                                                aria-invalid={Boolean(
                                                    errors.start_time,
                                                )}
                                            />
                                            <input
                                                type="hidden"
                                                name="start_time"
                                                value={startTime}
                                            />
                                            <InputError
                                                message={errors.start_time}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>End</Label>
                                            <TimePicker
                                                id="end_time"
                                                value={endTime}
                                                onChange={setEndTime}
                                                aria-invalid={Boolean(
                                                    errors.end_time ||
                                                        invalidWindow,
                                                )}
                                            />
                                            <input
                                                type="hidden"
                                                name="end_time"
                                                value={endTime}
                                            />
                                            <InputError
                                                message={
                                                    errors.end_time ||
                                                    (invalidWindow
                                                        ? 'End time must be after start time on the same day.'
                                                        : undefined)
                                                }
                                            />
                                        </div>
                                    </div>

                                    {startTime && endTime && !invalidWindow ? (
                                        <p className="text-muted-foreground text-sm">
                                            Window:{' '}
                                            {formatWindow(startTime, endTime)}
                                        </p>
                                    ) : null}
                                </Section>

                                <div className="flex flex-wrap gap-3">
                                    <Button
                                        type="submit"
                                        disabled={
                                            processing || invalidWindow
                                        }
                                    >
                                        {processing ? (
                                            <Spinner className="size-4" />
                                        ) : null}
                                        {isEdit
                                            ? 'Save shift'
                                            : 'Create shift'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link href="/platform/delivery-shifts">
                                            Cancel
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        );
                    }}
                </Form>
            </div>
        </>
    );
}

PlatformDeliveryShiftForm.layout = {
    breadcrumbs: [
        { title: 'Delivery shifts', href: '/platform/delivery-shifts' },
        { title: 'Form', href: '/platform/delivery-shifts/create' },
    ],
};
