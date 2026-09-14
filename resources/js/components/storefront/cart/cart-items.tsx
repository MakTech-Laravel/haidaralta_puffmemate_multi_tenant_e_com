import { Link } from '@inertiajs/react';
import { MapPin, Minus, Plus, Trash2 } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    formatMoney,
    type CartBranchOption,
    type CartGroup,
} from '@/lib/storefront-cart';
import { cn } from '@/lib/utils';
import { category as shopCategory } from '@/routes/shop';

type CartItemsProps = {
    groups: CartGroup[];
    branches: CartBranchOption[];
    onToggleGroup: (groupId: string, selected: boolean) => void;
    onToggleItem: (groupId: string, itemId: string, selected: boolean) => void;
    onQuantityChange: (groupId: string, itemId: string, quantity: number) => void;
    onBranchChange: (groupId: string, itemId: string, branchId: string) => void;
    onRemoveItem: (groupId: string, itemId: string) => void;
    className?: string;
};

export function CartItems({
    groups,
    branches,
    onToggleGroup,
    onToggleItem,
    onQuantityChange,
    onBranchChange,
    onRemoveItem,
    className,
}: CartItemsProps) {
    return (
        <div className={cn('flex flex-col gap-4', className)}>
            {groups.map((group) => (
                <section
                    key={group.id}
                    className="border-border bg-background overflow-hidden rounded-xl border"
                >
                    <div className="border-border flex items-center gap-3 border-b px-4 py-3">
                        <Checkbox
                            checked={group.selected}
                            onCheckedChange={(checked) =>
                                onToggleGroup(group.id, checked === true)
                            }
                            aria-label={`Select ${group.branchName}`}
                        />
                        <MapPin className="text-primary size-6 bg-primary/10 p-1 shrink-0" aria-hidden />
                        <h2 className="text-foreground text-base font-bold">
                            {group.branchName}
                        </h2>
                        <span className="text-muted-foreground text-sm font-medium">
                            {group.items.length} item{group.items.length === 1 ? '' : 's'}
                        </span>
                    </div>

                    <ul className="divide-border divide-y mt-4">
                        {group.items.map((item) => (
                            <li
                                key={item.id}
                                className="flex flex-col gap-4 p-4 xl:flex-row xl:items-center xl:gap-6"
                            >
                                <div className="flex min-w-0 flex-1 gap-3">
                                    <Checkbox
                                        checked={item.selected}
                                        onCheckedChange={(checked) =>
                                            onToggleItem(
                                                group.id,
                                                item.id,
                                                checked === true,
                                            )
                                        }
                                        className="mt-1"
                                        aria-label={`Select ${item.name}`}
                                    />
                                    <div className="bg-muted relative size-16 shrink-0 overflow-hidden rounded-lg sm:size-20">
                                        <div
                                            className="absolute inset-0 bg-cover bg-center"
                                            style={{
                                                backgroundImage: `url(${item.image})`,
                                            }}
                                            role="img"
                                            aria-label={item.name}
                                        />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-muted-foreground text-xs font-extrabold uppercase">
                                            {item.category}
                                        </p>
                                        <p className="text-foreground text-sm font-bold sm:text-base">
                                            {item.name}
                                        </p>
                                        <p className="text-muted-foreground mt-0.5 text-xs font-normal sm:text-sm">
                                            {item.description}
                                        </p>
                                        <p
                                            className={cn(
                                                'mt-2 inline-flex items-center gap-1.5 text-xs font-medium',
                                                item.unavailable
                                                    ? 'text-destructive'
                                                    : 'text-tertiary',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'size-1.5 rounded-full',
                                                    item.unavailable
                                                        ? 'bg-destructive'
                                                        : 'bg-tertiary',
                                                )}
                                                aria-hidden
                                            />
                                            {item.unavailable
                                                ? 'Unavailable'
                                                : `${item.available} available`}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-end xl:w-auto xl:shrink-0 xl:flex-nowrap">
                                    <div className="w-full shrink-0 sm:w-44">
                                        <p className="text-muted-foreground mb-1 text-xs font-extrabold uppercase leading-none">
                                            Branches
                                        </p>
                                        <Select
                                            value={item.branchId}
                                            onValueChange={(value) =>
                                                onBranchChange(
                                                    group.id,
                                                    item.id,
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="border-primary/50 h-9 w-full rounded-none text-sm font-semibold shadow-none">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {branches.map((branch) => (
                                                    <SelectItem
                                                        key={branch.id}
                                                        value={branch.id}
                                                    >
                                                        {branch.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="flex h-9 items-center gap-3">
                                        <div className="border-primary/50 flex h-9 items-center border">
                                            <button
                                                type="button"
                                                className="text-foreground flex size-9 items-center justify-center"
                                                onClick={() =>
                                                    onQuantityChange(
                                                        group.id,
                                                        item.id,
                                                        Math.max(
                                                            1,
                                                            item.quantity - 1,
                                                        ),
                                                    )
                                                }
                                                aria-label="Decrease quantity"
                                            >
                                                <Minus className="size-3.5" />
                                            </button>
                                            <span className="text-foreground min-w-8 text-center text-sm font-semibold">
                                                {item.quantity}
                                            </span>
                                            <button
                                                type="button"
                                                className="text-foreground flex size-9 items-center justify-center"
                                                onClick={() =>
                                                    onQuantityChange(
                                                        group.id,
                                                        item.id,
                                                        item.quantity + 1,
                                                    )
                                                }
                                                aria-label="Increase quantity"
                                            >
                                                <Plus className="size-3.5" />
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            className="text-muted-foreground hover:text-destructive flex size-9 shrink-0 items-center justify-center"
                                            onClick={() =>
                                                onRemoveItem(group.id, item.id)
                                            }
                                            aria-label={`Remove ${item.name}`}
                                        >
                                            <Trash2 className="size-4" />
                                        </button>

                                        <p className="text-primary flex h-9 min-w-16 items-center justify-end text-base font-bold">
                                            {formatMoney(
                                                item.unitPrice * item.quantity,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            ))}

            <Link
                href={shopCategory.url('cigarettes')}
                prefetch
                className="text-primary inline-flex items-center gap-1 text-sm font-semibold hover:underline"
            >
                <Plus className="size-4" />
                Add another item
            </Link>
        </div>
    );
}
