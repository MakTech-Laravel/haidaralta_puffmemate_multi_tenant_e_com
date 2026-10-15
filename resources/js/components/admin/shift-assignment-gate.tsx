import { LockKeyhole } from 'lucide-react';
import { ShiftPersonPicker } from '@/components/admin/shift-person-picker';
import type {
    BranchOption,
    ShiftAssignmentPeopleKind,
} from '@/components/admin/workforce-types';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

export function ShiftAssignmentGate({
    branch,
    branchLocked,
    branchOptionsUrl,
    peopleKind,
    personId,
    personName,
    peopleOptionsUrl,
    loading = false,
    onBranchChange,
    onPeopleKindChange,
    onPersonChange,
}: {
    branch: BranchOption | null;
    branchLocked: boolean;
    branchOptionsUrl: string | null;
    peopleKind: ShiftAssignmentPeopleKind | null;
    personId: number | null;
    personName?: string | null;
    peopleOptionsUrl: string | null;
    loading?: boolean;
    onBranchChange: (branchId: number | null) => void;
    onPeopleKindChange: (kind: ShiftAssignmentPeopleKind | null) => void;
    onPersonChange: (userId: number | null) => void;
}) {
    return (
        <section
            aria-busy={loading}
            className="border-border/80 bg-card relative rounded-2xl border p-4 shadow-sm sm:p-6"
        >
            {loading ? (
                <div className="text-muted-foreground absolute top-3 right-4 flex items-center gap-2 text-xs">
                    <Spinner />
                    Loading…
                </div>
            ) : null}
            <div className="grid gap-5 lg:grid-cols-3">
                <div className="space-y-2">
                    <Label htmlFor="shift-branch">Branch</Label>
                    {branchLocked ? (
                        <div
                            id="shift-branch"
                            className="border-input bg-muted/40 flex h-11 items-center justify-between rounded-md border px-3 text-sm"
                        >
                            <span className="truncate">
                                {branch?.name ?? 'Branch'}
                            </span>
                            <LockKeyhole className="text-muted-foreground size-4" />
                        </div>
                    ) : (
                        <InfiniteScrollCombobox
                            id="shift-branch"
                            optionsUrl={branchOptionsUrl ?? ''}
                            value={branch ? String(branch.id) : null}
                            selectedLabel={branch?.name}
                            disabled={loading || branchOptionsUrl === null}
                            onValueChange={(next) =>
                                onBranchChange(
                                    next === null ? null : Number(next),
                                )
                            }
                            placeholder="Choose a branch"
                            searchPlaceholder="Search branches…"
                            emptyText="No branches found."
                            loadErrorText="Could not load branches."
                            descriptionKey={null}
                            className="h-11"
                        />
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="shift-people-kind">User type</Label>
                    <Select
                        value={peopleKind ?? undefined}
                        disabled={loading || branch === null}
                        onValueChange={(value) =>
                            onPeopleKindChange(
                                value as ShiftAssignmentPeopleKind,
                            )
                        }
                    >
                        <SelectTrigger
                            id="shift-people-kind"
                            className="h-11 w-full"
                        >
                            <SelectValue placeholder="Choose a user type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="staff">Staff</SelectItem>
                            <SelectItem value="driver">Driver</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <ShiftPersonPicker
                    optionsUrl={peopleOptionsUrl}
                    value={personId}
                    selectedLabel={personName}
                    disabled={loading || branch === null || peopleKind === null}
                    onChange={onPersonChange}
                />
            </div>
        </section>
    );
}
