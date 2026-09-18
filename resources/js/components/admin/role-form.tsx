import { Form, Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import {
    PermissionSelector,
    type PermissionGroup,
} from '@/components/permission-selector';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type FormMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

type FormAction = {
    action: string;
    method?: string;
};

const TAKEN_ROLE_NAME_MESSAGE = 'A role with this name already exists.';

function isRoleNameTaken(value: string, existingNames: string[]): boolean {
    const needle = value.trim().toLowerCase();

    if (needle === '') {
        return false;
    }

    return existingNames.some((name) => name.toLowerCase() === needle);
}

interface RoleFormProps {
    action: FormAction;
    groups: PermissionGroup[];
    existingNames?: string[];
    isEdit?: boolean;
    locked?: boolean;
    defaults?: { name: string; permissions: string[] };
    cancelHref: string;
    submitLabel?: string;
}

function sectionClassName(): string {
    return cn(
        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
        'sm:p-6',
    );
}

export function RoleForm({
    action,
    groups,
    existingNames = [],
    isEdit = false,
    locked = false,
    defaults,
    cancelHref,
    submitLabel,
}: RoleFormProps) {
    const [selected, setSelected] = useState<string[]>(
        defaults?.permissions ?? [],
    );
    const [nameTaken, setNameTaken] = useState(false);

    return (
        <Form
            action={action.action}
            method={(action.method ?? 'post').toLowerCase() as FormMethod}
            className="w-full space-y-6"
            options={{ preserveScroll: true }}
            validationTimeout={500}
        >
            {({ processing, errors, validate }) => (
                <>
                    {locked ? (
                        <Alert>
                            <ShieldAlert />
                            <AlertTitle>Super Admin role</AlertTitle>
                            <AlertDescription>
                                This role implicitly receives every permission
                                via Gate, so it cannot be renamed or have
                                permissions edited.
                            </AlertDescription>
                        </Alert>
                    ) : null}

                    <section className={sectionClassName()}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Details
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                The name shown when assigning this role to an
                                account. Names must be unique.
                            </p>
                        </div>
                        <div className="flex max-w-md flex-col gap-1.5">
                            <Label htmlFor="name">Role name</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={defaults?.name ?? ''}
                                required
                                disabled={locked}
                                readOnly={locked}
                                placeholder="Store manager"
                                autoComplete="off"
                                aria-invalid={Boolean(errors.name) || nameTaken}
                                onBlur={(event) => {
                                    if (locked) {
                                        return;
                                    }

                                    setNameTaken(
                                        isRoleNameTaken(
                                            event.currentTarget.value,
                                            existingNames,
                                        ),
                                    );
                                    validate('name');
                                }}
                            />
                            {locked ? (
                                <input
                                    type="hidden"
                                    name="name"
                                    value={defaults?.name ?? ''}
                                />
                            ) : null}
                            <InputError
                                message={
                                    errors.name ??
                                    (nameTaken
                                        ? TAKEN_ROLE_NAME_MESSAGE
                                        : undefined)
                                }
                            />
                        </div>
                    </section>

                    <section className={sectionClassName()}>
                        <div className="mb-3 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Permissions
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Pick a group, then toggle individual
                                permissions. Accounts with this role inherit the
                                selection.
                            </p>
                        </div>
                        <PermissionSelector
                            groups={groups}
                            selected={selected}
                            onChange={setSelected}
                            disabled={locked}
                        />
                        <InputError message={errors.permissions} />
                    </section>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || locked || nameTaken}
                        >
                            {processing ? <Spinner /> : null}
                            {submitLabel ??
                                (isEdit ? 'Save changes' : 'Create role')}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
