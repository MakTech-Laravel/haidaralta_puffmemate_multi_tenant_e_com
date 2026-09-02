import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export type PermissionOption = {
    value: string;
    label: string;
};

export type PermissionGroup = {
    group: string;
    permissions: PermissionOption[];
};

export function PermissionSelector({
    groups,
    selected,
    onChange,
    disabled = false,
}: {
    groups: PermissionGroup[];
    selected: string[];
    onChange: (next: string[]) => void;
    disabled?: boolean;
}) {
    const toggle = (value: string, checked: boolean) => {
        if (checked) {
            onChange([...selected, value]);

            return;
        }

        onChange(selected.filter((item) => item !== value));
    };

    return (
        <div className="space-y-6">
            {selected.map((permission) => (
                <input
                    key={permission}
                    type="hidden"
                    name="permissions[]"
                    value={permission}
                />
            ))}

            {groups.map((group) => (
                <div key={group.group} className="space-y-3">
                    <h3 className="text-sm font-medium">{group.group}</h3>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {group.permissions.map((permission) => {
                            const checked = selected.includes(permission.value);

                            return (
                                <div
                                    key={permission.value}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`permission-${permission.value}`}
                                        checked={checked}
                                        disabled={disabled}
                                        onCheckedChange={(value) =>
                                            toggle(
                                                permission.value,
                                                value === true,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor={`permission-${permission.value}`}
                                        className="font-normal"
                                    >
                                        {permission.label}
                                    </Label>
                                </div>
                            );
                        })}
                    </div>
                </div>
            ))}
        </div>
    );
}
