import type { PrivacyBlock, PrivacySection as PrivacySectionData } from '@/lib/storefront-privacy';
import { cn } from '@/lib/utils';

type PrivacySectionProps = {
    section: PrivacySectionData;
    className?: string;
};

function SectionBlocks({ blocks }: { blocks: PrivacyBlock[] }) {
    return (
        <div className="text-muted-foreground mt-4 space-y-4 text-sm leading-relaxed sm:text-base">
            {blocks.map((block, index) => {
                if (block.type === 'paragraph') {
                    return <p key={`p-${index}`}>{block.text}</p>;
                }

                return (
                    <ul key={`l-${index}`} className="space-y-2 pl-1">
                        {block.items.map((item) => (
                            <li key={item} className="flex items-start gap-2">
                                <span
                                    className="bg-foreground/40 mt-2 size-1 shrink-0 rounded-full"
                                    aria-hidden
                                />
                                <span>{item}</span>
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
        <section
            id={section.id}
            className={cn('scroll-mt-28', className)}
        >
            <h2 className="text-foreground text-base font-bold tracking-tight uppercase sm:text-lg">
                {section.number}. {section.title}
            </h2>
            <SectionBlocks blocks={section.blocks} />
        </section>
    );
}
