import type { MouseEvent } from 'react';
import { Button } from '@/components/ui/button';

type NotificationLinkButtonProps = {
    href: string;
    label?: string | null;
    size?: 'default' | 'sm';
    className?: string;
    onClick?: (event: MouseEvent<HTMLAnchorElement>) => void;
};

export function NotificationLinkButton({
    href,
    label,
    size = 'default',
    className,
    onClick,
}: NotificationLinkButtonProps) {
    const text = label?.trim() || 'Open';

    return (
        <Button asChild size={size} className={className}>
            <a href={href} onClick={onClick}>
                {text}
            </a>
        </Button>
    );
}
