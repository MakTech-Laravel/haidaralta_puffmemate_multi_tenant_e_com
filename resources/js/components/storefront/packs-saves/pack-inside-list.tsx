import { CircleCheck } from 'lucide-react';
import { cn } from '@/lib/utils';

type PackInsideListProps = {
    title: string;
    items: { name: string; quantity: number }[];
    className?: string;
};

export function PackInsideList({ title, items, className }: PackInsideListProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className={cn('bg-muted/60 rounded-lg p-4', className)}>
            <p className="text-foreground text-[11px] font-bold tracking-wider uppercase">
                {title}
            </p>
            <ul className="mt-3 grid gap-2 sm:grid-cols-2">
                {items.map((item, index) => (
                    <li
                        key={`${item.name}-${index}`}
                        className="text-foreground flex items-start gap-2 text-sm"
                    >
                        <CircleCheck className="text-primary mt-0.5 size-4 shrink-0" />
                        <span className="min-w-0">
                            {item.quantity}x {item.name}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
