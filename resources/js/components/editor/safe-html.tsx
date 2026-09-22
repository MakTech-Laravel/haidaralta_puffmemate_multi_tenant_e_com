import { sanitizeHtml } from '@/lib/sanitize-html';
import { cn } from '@/lib/utils';

type SafeHtmlProps = {
    html: string | null | undefined;
    className?: string;
};

export function SafeHtml({ html, className }: SafeHtmlProps) {
    const clean = sanitizeHtml(html);

    if (!clean) {
        return null;
    }

    return (
        <div
            className={cn(
                'cms-rich-text text-sm leading-relaxed',
                '[&_h2]:mt-6 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-semibold',
                '[&_h3]:mt-4 [&_h3]:mb-1.5 [&_h3]:text-lg [&_h3]:font-semibold',
                '[&_p]:mb-3 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:pl-5',
                '[&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_a]:text-primary [&_a]:underline',
                '[&_img]:my-3 [&_img]:max-w-full [&_img]:rounded-md',
                className,
            )}
            dangerouslySetInnerHTML={{ __html: clean }}
        />
    );
}
