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

type FormMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

type FormAction = {
    action: string;
    method?: string;
};

interface RoleFormProps {
    action: FormAction;
    groups: PermissionGroup[];
    isEdit?: boolean;
    locked?: boolean;
    defaults?: { name: string; permissions: string[] };
    cancelHref: string;
    submitLabel?: string;
}

export function RoleForm({
    action,
    groups,
    isEdit = false,
    locked = false,
    defaults,
    cancelHref,
    submitLabel,
}: RoleFormProps) {
    const [selected, setSelected] = useState<string[]>(
        defaults?.permissions ?? [],
    );

    return (
        <Form
            action={action.action}
            method={(action.method ?? 'post').toLowerCase() as FormMethod}
            className="space-y-6"
            options={{ preserveScroll: true }}
            validationTimeout={500}
        >
            {({ processing, errors, validate }) => (
                <>
                    {locked && (
                        <Alert>
                            <ShieldAlert className="h-4 w-4" />
                            <AlertTitle>Super Admin role</AlertTitle>
                            <AlertDescription>
                                This role implicitly receives every permission
                                via{' '}
                                <code className="bg-muted rounded px-1 py-0.5 text-xs">
                                    Gate::before
                                </code>
                                , so it cannot be renamed or have permissions
                                edited.
                            </AlertDescription>
                        </Alert>
                    )}

                    <div className="grid max-w-md gap-2">
                        <Label htmlFor="name">Role name</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={defaults?.name ?? ''}
                            required
                            disabled={locked}
                            readOnly={locked}
                            placeholder="e.g. auditor"
                            onBlur={() => {
                                if (!locked) {
                                    validate('name');
                                }
                            }}
                        />
                        {locked && (
                            <input
                                type="hidden"
                                name="name"
                                value={defaults?.name ?? ''}
                            />
                        )}
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-3">
                        <div>
                            <Label>Permissions</Label>
                            <p className="text-muted-foreground text-xs">
                                Toggle individual permissions or whole groups at
                                once.
                            </p>
                        </div>
                        <PermissionSelector
                            groups={groups}
                            selected={selected}
                            onChange={setSelected}
                            disabled={locked}
                        />
                        <InputError message={errors.permissions} />
                    </div>

                    <div className="flex items-center gap-3 border-t pt-5">
                        <Button type="submit" disabled={processing || locked}>
                            {submitLabel ??
                                (isEdit ? 'Save changes' : 'Create role')}
                        </Button>
                        <Button variant="secondary" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
