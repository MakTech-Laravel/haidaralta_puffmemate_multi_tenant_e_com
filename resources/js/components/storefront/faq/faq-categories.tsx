import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { SafeHtml } from '@/components/editor/safe-html';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { resolveLucideIcon } from '@/lib/cms-icons';
import { cn } from '@/lib/utils';

type FaqItem = {
    id: string;
    question: string;
    answer: string;
    defaultOpen?: boolean;
};

type FaqCategory = {
    id: string;
    title: string;
    icon: string;
    items: FaqItem[];
};

type FaqCategoriesProps = {
    className?: string;
    categories: FaqCategory[];
};

function looksLikeHtml(value: string): boolean {
    return /<\/?[a-z][\s\S]*>/i.test(value);
}

function FaqQuestion({ item }: { item: FaqItem }) {
    const [open, setOpen] = useState(item.defaultOpen ?? false);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <div
                className={cn(
                    'border-border overflow-hidden rounded-lg border transition-colors',
                    open && 'bg-muted/40 border-border',
                )}
            >
                <CollapsibleTrigger className="flex w-full items-start gap-3 px-4 py-3.5 text-left">
                    <span
                        className="bg-primary mt-2 size-1.5 shrink-0 rounded-full"
                        aria-hidden
                    />
                    <span className="text-foreground font-inter min-w-0 flex-1 text-sm font-bold sm:text-lg">
                        {item.question}
                    </span>
                    <ChevronDown
                        className={cn(
                            'text-muted-foreground mt-0.5 size-4 shrink-0 transition-transform',
                            open && 'text-primary rotate-180',
                        )}
                        aria-hidden
                    />
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div className="border-border border-t px-4 pt-3 pb-4 pl-[1.625rem]">
                        {looksLikeHtml(item.answer) ? (
                            <SafeHtml
                                html={item.answer}
                                className="text-muted-foreground font-inter text-base leading-relaxed font-normal"
                            />
                        ) : (
                            <p className="text-muted-foreground font-inter text-base leading-relaxed font-normal">
                                {item.answer}
                            </p>
                        )}
                    </div>
                </CollapsibleContent>
            </div>
        </Collapsible>
    );
}

export function FaqCategories({ className, categories }: FaqCategoriesProps) {
    return (
        <section className={cn('bg-background pb-16 sm:pb-20', className)}>
            <div className="container mx-auto max-w-3xl space-y-10 px-4 sm:max-w-4xl sm:space-y-12 sm:px-6">
                {categories.map((category) => {
                    const Icon = resolveLucideIcon(category.icon);
                    const count = category.items.length;

                    return (
                        <div key={category.id}>
                            <div className="mb-4 flex items-center justify-between gap-3">
                                <div className="flex min-w-0 items-center gap-3">
                                    <span className="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-full">
                                        <Icon className="size-4" aria-hidden />
                                    </span>
                                    <h2 className="text-foreground text-lg font-bold tracking-tight sm:text-2xl">
                                        {category.title}
                                    </h2>
                                </div>
                                <span className="text-muted-foreground shrink-0 text-[0.65rem] font-extrabold tracking-wider uppercase">
                                    {count} question{count === 1 ? '' : 's'}
                                </span>
                            </div>

                            <ul className="space-y-2.5">
                                {category.items.map((item) => (
                                    <li key={item.id}>
                                        <FaqQuestion item={item} />
                                    </li>
                                ))}
                            </ul>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
