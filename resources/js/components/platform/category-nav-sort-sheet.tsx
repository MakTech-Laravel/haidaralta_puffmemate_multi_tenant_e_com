import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import InputError from '@/components/input-error';

export type CategoryNavSortTarget = {
    name: string;
    slug: string;
    nav_sort: number | null;
};

export function CategoryNavSortSheet({
    open,
    onOpenChange,
    category,
    actionUrl,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    category: CategoryNavSortTarget | null;
    actionUrl: string | null;
}) {
    return (
        <Sheet
            open={open && category !== null && actionUrl !== null}
            onOpenChange={onOpenChange}
        >
            <SheetContent side="right" className="sm:max-w-md">
                {category && actionUrl ? (
                <Form
                    key={category.slug}
                    action={actionUrl}
                    method="put"
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="flex h-full flex-col"
                >
                    {({ errors, processing }) => (
                        <>
                            <SheetHeader>
                                <SheetTitle>Set nav position</SheetTitle>
                                <SheetDescription>
                                    Choose the header order for {category.name}.
                                    Lower numbers appear first.
                                </SheetDescription>
                            </SheetHeader>
                            <div className="flex flex-1 flex-col gap-2 px-4">
                                <Label htmlFor="nav_sort">Position</Label>
                                <Input
                                    id="nav_sort"
                                    name="nav_sort"
                                    type="number"
                                    min={0}
                                    max={65535}
                                    defaultValue={category.nav_sort ?? 0}
                                    required
                                />
                                <InputError message={errors.nav_sort} />
                            </div>
                            <SheetFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Save position
                                </Button>
                            </SheetFooter>
                        </>
                    )}
                </Form>
                ) : null}
            </SheetContent>
        </Sheet>
    );
}
