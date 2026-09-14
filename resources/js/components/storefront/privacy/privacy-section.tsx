import type { ReactNode } from 'react';
import type {
    PrivacyBlock,
    PrivacySection as PrivacySectionData,
} from '@/lib/storefront-privacy';
import { cn } from '@/lib/utils';

type PrivacySectionProps = {
    section: PrivacySectionData;
    className?: string;
};

const EMAIL_PATTERN = /([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/g;

function isEmail(value: string): boolean {
    return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value);
}

function linkifyEmails(text: string): ReactNode[] {
    return text.split(EMAIL_PATTERN).map((part, index) => {
        if (isEmail(part)) {
            return (
                <a
                    key={`${part}-${index}`}
                    href={`mailto:${part}`}
                    className="text-primary font-medium hover:underline"
                >
                    {part}
                </a>
            );
        }

        return part;
    });
}

function SectionBlocks({ blocks }: { blocks: PrivacyBlock[] }) {
    return (
        <div className="mt-4 space-y-4 text-sm leading-relaxed sm:text-base">
            {blocks.map((block, index) => {
                if (block.type === 'subheading') {
                    return (
                        <h3
                            key={`s-${index}`}
                            className="text-foreground pt-1 text-sm font-bold sm:text-base"
                        >
                            {block.text}
                        </h3>
                    );
                }

                if (block.type === 'paragraph') {
                    return (
                        <p
                            key={`p-${index}`}
                            className="text-muted-foreground"
                        >
                            {linkifyEmails(block.text)}
                        </p>
                    );
                }

                return (
                    <ul key={`l-${index}`} className="space-y-2 pl-1">
                        {block.items.map((item) => (
                            <li
                                key={item}
                                className="text-muted-foreground flex items-start gap-2"
                            >
                                <span
                                    className="bg-foreground mt-2 size-1 shrink-0 rounded-full"
                                    aria-hidden
                                />
                                <span>{linkifyEmails(item)}</span>
                            </li>
                        ))}
                    </ul>
                );
            })}
        </div>
    );
}

export function PrivacySection({ section, className }: PrivacySectionProps) {
    return (
        <section id={section.id} className={cn('scroll-mt-28', className)}>
            <h2 className="text-foreground text-base font-bold tracking-tight uppercase sm:text-2xl">
                {section.number}. {section.title}
            </h2>
            <SectionBlocks blocks={section.blocks} />
        </section>
    );
}
