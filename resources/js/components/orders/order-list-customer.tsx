import { Link } from '@inertiajs/react';

export function OrderListCustomer({
    name,
    phone,
    href,
}: {
    name: string;
    phone: string | null;
    href?: string | null;
}) {
    const content = (
        <span className="flex min-w-0 flex-col">
            <span className="truncate font-medium">{name}</span>
            {phone ? (
                <span className="text-muted-foreground truncate text-xs">
                    {phone}
                </span>
            ) : null}
        </span>
    );

    if (!href) {
        return content;
    }

    return (
        <Link href={href} className="hover:text-primary block min-w-0">
            {content}
        </Link>
    );
}
